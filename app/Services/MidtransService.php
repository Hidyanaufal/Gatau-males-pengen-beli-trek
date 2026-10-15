<?php

namespace App\Services;

use DomainException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class MidtransService
{
    /**
     * Minta QRIS ke Midtrans.
     *
     * @param  string  $midtransOrderId  ID unik untuk satu kali percobaan bayar
     * @param  int     $amount           Total dalam Rupiah (bilangan bulat)
     * @return array{transaction_id: string, qr_url: string, expiry_time: ?string, raw: array}
     */
    public function createQris(string $midtransOrderId, int $amount): array
    {
        $serverKey = config('midtrans.server_key');

        if (empty($serverKey)) {
            throw new DomainException('Pembayaran belum dikonfigurasi.');
        }

        try {
            $response = Http::withBasicAuth($serverKey, '')
                ->acceptJson()
                ->asJson()
                ->timeout(15)
                ->post(config('midtrans.base_url') . '/v2/charge', [
                    'payment_type' => 'qris',
                    'transaction_details' => [
                        'order_id' => $midtransOrderId,
                        'gross_amount' => $amount,
                    ],
                    'qris' => [
                        'acquirer' => 'gopay',
                    ],
                ]);
        } catch (Throwable $e) {
            Log::error('Midtrans tidak bisa dihubungi', ['error' => $e->getMessage()]);
            throw new DomainException('Layanan pembayaran sedang tidak tersedia. Coba lagi nanti.');
        }

        $data = $response->json() ?? [];

        // Midtrans mengembalikan status_code "201" kalau charge berhasil dibuat
        if (! $response->successful() || ($data['status_code'] ?? null) !== '201') {
            Log::error('Midtrans menolak charge', [
                'http_status' => $response->status(),
                'status_code' => $data['status_code'] ?? null,
                'message' => $data['status_message'] ?? null,
            ]);
            throw new DomainException('Gagal membuat QR pembayaran.');
        }

        $qrUrl = collect($data['actions'] ?? [])
            ->firstWhere('name', 'generate-qr-code')['url'] ?? null;

        if (! $qrUrl) {
            Log::error('Respons Midtrans tidak berisi URL QR');
            throw new DomainException('Gagal membuat QR pembayaran.');
        }

        return [
            'transaction_id' => $data['transaction_id'],
            'qr_url' => $qrUrl,
            'expiry_time' => $data['expiry_time'] ?? null,
            'raw' => $data,
        ];
    }
}