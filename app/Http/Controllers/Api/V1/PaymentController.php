<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Order;
use App\Models\Payment;
use App\Services\MidtransService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function __construct(private readonly MidtransService $midtrans)
    {
    }

    public function pay(Request $request, int $id): JsonResponse
    {
        // Hanya pesanan milik pengguna yang login; milik orang lain dianggap tidak ada
        $order = $request->user()->orders()->find($id);

        if (! $order) {
            return ApiResponse::error('Pesanan tidak ditemukan.', 404);
        }

        try {
            $payment = DB::transaction(function () use ($order) {
                // Kunci baris pesanan supaya tap ganda tidak membuat dua QR
                $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

                if ($order->status === OrderStatus::Cancelled) {
                    throw new DomainException('Pesanan sudah dibatalkan.');
                }

                if ($order->payment_status === PaymentStatus::Paid) {
                    throw new DomainException('Pesanan ini sudah lunas.');
                }

                // Kalau masih ada QR yang belum kedaluwarsa, pakai lagi
                $existing = $order->payments()
                    ->where('status', PaymentStatus::Pending->value)
                    ->latest('id')
                    ->first();

                if ($existing && $this->stillValid($existing)) {
                    return $existing;
                }

                // ID unik tiap percobaan bayar (Midtrans menolak ID yang sama dipakai dua kali)
                $midtransOrderId = $order->order_number . '-' . Str::upper(Str::random(6));

                $qris = $this->midtrans->createQris(
                    $midtransOrderId,
                    (int) round((float) $order->total)
                );

                $payment = Payment::create([
                    'order_id' => $order->id,
                    'method' => 'qris',
                    'provider' => 'midtrans',
                    'provider_transaction_id' => $qris['transaction_id'],
                    'amount' => $order->total,
                    'status' => PaymentStatus::Pending,
                    'payload' => [
                        'midtrans_order_id' => $midtransOrderId,
                        'qr_url' => $qris['qr_url'],
                        'expiry_time' => $qris['expiry_time'],
                    ],
                ]);

                $order->payment_status = PaymentStatus::Pending;
                $order->save();

                return $payment;
            });
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success([
            'order_number' => $order->order_number,
            'amount' => $payment->amount,
            'status' => $payment->status->value,
            'status_label' => $payment->status->label(),
            'qr_url' => $payment->payload['qr_url'] ?? null,
            'expiry_time' => $payment->payload['expiry_time'] ?? null,
        ], 'QR pembayaran siap.');
    }

    private function stillValid(Payment $payment): bool
    {
        $expiry = $payment->payload['expiry_time'] ?? null;

        if (! $expiry || empty($payment->payload['qr_url'])) {
            return false;
        }

        return Carbon::parse($expiry, 'Asia/Jakarta')->isFuture();
    }
}