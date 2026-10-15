<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ServiceUnit;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    /**
     * Membuat pesanan. Harga dan total SELALU dihitung dari tabel services,
     * angka dari aplikasi Android tidak dipakai.
     *
     * @param  array{fulfillment_type:string, address?:?string, scheduled_at?:?string, notes?:?string, items:array<int,array{service_id:int|string, quantity:int|float|string}>}  $data
     *
     * @throws DomainException
     */
    public function create(User $user, array $data): Order
    {
        return DB::transaction(function () use ($user, $data) {
            $ids = collect($data['items'])->pluck('service_id')->map(fn ($id) => (int) $id)->unique()->all();

            $services = Service::query()
                ->whereIn('id', $ids)
                ->where('is_active', true)
                ->get()
                ->keyBy('id');

            $lines = [];
            $totalCents = 0;

            foreach ($data['items'] as $item) {
                /** @var Service|null $service */
                $service = $services->get((int) $item['service_id']);

                if (! $service) {
                    throw new DomainException('Salah satu layanan tidak tersedia.');
                }

                // Hitung dengan bilangan bulat (sen) agar tidak ada selisih pembulatan.
                $qtyHundredths = (int) round(((float) $item['quantity']) * 100);

                if ($qtyHundredths <= 0) {
                    throw new DomainException('Jumlah harus lebih dari 0.');
                }

                if ($service->unit === ServiceUnit::Item && $qtyHundredths % 100 !== 0) {
                    throw new DomainException("Layanan '{$service->name}' hanya bisa dipesan dalam jumlah bulat.");
                }

                $priceCents = (int) round(((float) $service->price) * 100);
                $subtotalCents = intdiv($priceCents * $qtyHundredths + 50, 100);
                $totalCents += $subtotalCents;

                $lines[] = [
                    'service_id' => $service->id,
                    'service_name' => $service->name,
                    'unit_price' => $this->money($priceCents),
                    'quantity' => $this->money($qtyHundredths),
                    'subtotal' => $this->money($subtotalCents),
                ];
            }

            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $user->id,
                'status' => OrderStatus::Pending,
                'payment_status' => PaymentStatus::Unpaid,
                'fulfillment_type' => $data['fulfillment_type'],
                'total' => $this->money($totalCents),
                'address' => $data['address'] ?? null,
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'notes' => $data['notes'] ?? null,
                'ordered_at' => now(),
            ]);

            $order->items()->createMany($lines);

            return $order->load('items');
        });
    }

    /**
     * @throws DomainException
     */
    public function cancel(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $fresh = Order::query()->lockForUpdate()->findOrFail($order->id);

            if (! self::canCancel($fresh)) {
                throw new DomainException('Pesanan tidak dapat dibatalkan pada status ini.');
            }

            $fresh->update(['status' => OrderStatus::Cancelled]);

            return $fresh->load('items');
        });
    }

    public static function canCancel(Order $order): bool
    {
        return $order->status->canTransitionTo(OrderStatus::Cancelled)
            && $order->payment_status !== PaymentStatus::Paid;
    }

    private function generateOrderNumber(): string
    {
        do {
            $number = 'LL-' . now()->format('ymd') . '-' . strtoupper(Str::random(5));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }

    /** Ubah bilangan bulat (sen / ratusan) menjadi string desimal 2 digit. */
    private function money(int $hundredths): string
    {
        return number_format($hundredths / 100, 2, '.', '');
    }
}
