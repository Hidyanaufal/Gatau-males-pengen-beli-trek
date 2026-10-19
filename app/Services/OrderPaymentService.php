<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderPaymentService
{
    public function __construct(private readonly MidtransService $midtrans)
    {
    }

    /**
     * Ambil QRIS yang masih berlaku untuk pesanan ini, atau buat yang baru.
     * Total selalu dibaca dari database, bukan dari input pengguna.
     *
     * @throws DomainException
     */
    public function getOrCreateQris(Order $order): Payment
    {
        return DB::transaction(function () use ($order) {
            // Kunci baris pesanan supaya klik ganda tidak membuat dua QR
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->status === OrderStatus::Cancelled) {
                throw new DomainException('Pesanan sudah dibatalkan.');
            }

            if ($order->payment_status === PaymentStatus::Paid) {
                throw new DomainException('Pesanan ini sudah lunas.');
            }

            $existing = $order->payments()
                ->where('status', PaymentStatus::Pending->value)
                ->latest('id')
                ->first();

            if ($existing && $this->stillValid($existing)) {
                return $existing;
            }

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
    }

    public function stillValid(Payment $payment): bool
    {
        $expiry = $payment->payload['expiry_time'] ?? null;

        if (! $expiry || empty($payment->payload['qr_url'])) {
            return false;
        }

        return Carbon::parse($expiry, 'Asia/Jakarta')->isFuture();
    }
}