<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MidtransWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $serverKey = (string) config('midtrans.server_key');

        $orderId = (string) $request->input('order_id');
        $statusCode = (string) $request->input('status_code');
        $grossAmount = (string) $request->input('gross_amount');
        $signature = (string) $request->input('signature_key');

        // 1. Validasi signature: SHA512(order_id + status_code + gross_amount + ServerKey)
        $expected = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        if ($serverKey === '' || ! hash_equals($expected, $signature)) {
            Log::warning('Webhook Midtrans: signature tidak valid', ['order_id' => $orderId]);

            return response()->json(['message' => 'Signature tidak valid.'], 403);
        }

        // 2. Cari pembayaran lewat ID transaksi Midtrans
        $transactionId = (string) $request->input('transaction_id');

        $payment = Payment::where('provider', 'midtrans')
            ->where('provider_transaction_id', $transactionId)
            ->first();

        if (! $payment) {
            // Contoh: tombol "Test notification" di dashboard. Balas 200 supaya tidak diulang terus.
            return response()->json(['message' => 'Diabaikan.']);
        }

        DB::transaction(function () use ($payment, $request, $grossAmount) {
            // Kunci baris supaya notifikasi ganda yang datang bersamaan tidak saling menimpa
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->first();
            $order = $payment->order()->lockForUpdate()->first();

            // 3. Idempotent: yang sudah lunas tidak boleh diubah lagi
            if ($payment->status === PaymentStatus::Paid) {
                return;
            }

            // 4. Jumlah uang harus sama dengan yang kita simpan
            if ((int) round((float) $grossAmount) !== (int) round((float) $payment->amount)) {
                Log::error('Webhook Midtrans: jumlah tidak cocok', ['payment_id' => $payment->id]);

                return;
            }

            $newStatus = match ($request->input('transaction_status')) {
                'settlement', 'capture' => PaymentStatus::Paid,
                'pending' => PaymentStatus::Pending,
                'expire' => PaymentStatus::Expired,
                'cancel', 'deny', 'failure' => PaymentStatus::Failed,
                default => null,
            };

            if ($newStatus === null) {
                return;
            }

            $payment->status = $newStatus;
            $payment->payload = array_merge($payment->payload ?? [], [
                'last_notification' => $request->only([
                    'transaction_status', 'transaction_time', 'settlement_time', 'status_code',
                ]),
            ]);

            if ($newStatus === PaymentStatus::Paid) {
                $payment->paid_at = now();
            }

            $payment->save();

            // Status pembayaran pesanan ikut diperbarui. Status pesanan sendiri tidak diubah di sini.
            if ($newStatus === PaymentStatus::Paid || $order->payment_status !== PaymentStatus::Paid) {
                $order->payment_status = $newStatus;
                $order->save();
            }
        });

        return response()->json(['message' => 'OK']);
    }
}