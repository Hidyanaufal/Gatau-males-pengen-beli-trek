<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $services = Service::orderBy('id')->get();

        if ($services->count() < 4) {
            $this->command?->error('Jalankan ServiceSeeder dulu.');

            return;
        }

        $people = [
            ['Budi Santoso', 'budi@demo.test', '081234560001', 'Jl. Melati No. 5, Semarang'],
            ['Siti Aminah', 'siti@demo.test', '081234560002', 'Jl. Kenanga No. 12, Semarang'],
            ['Andi Wijaya', 'andi@demo.test', '081234560003', 'Jl. Mawar No. 8, Semarang'],
        ];

        $customers = [];
        foreach ($people as [$name, $email, $phone, $address]) {
            $customers[] = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'phone' => $phone,
                    'address' => $address,
                ]
            );
        }

        // [no, pelanggan, layanan, jumlah, status, status bayar, hari lalu, jenis]
        $orders = [
            ['LL-DEMO-001', 0, 0, 3, 'pending',    'unpaid',  0, 'drop_off'],
            ['LL-DEMO-002', 1, 1, 2, 'confirmed',  'pending', 1, 'pickup_delivery'],
            ['LL-DEMO-003', 2, 2, 4, 'processing', 'paid',    2, 'pickup_delivery'],
            ['LL-DEMO-004', 0, 3, 1, 'ready',      'paid',    3, 'drop_off'],
            ['LL-DEMO-005', 1, 0, 5, 'completed',  'paid',    5, 'drop_off'],
            ['LL-DEMO-006', 2, 1, 2, 'completed',  'paid',    7, 'pickup_delivery'],
            ['LL-DEMO-007', 0, 2, 3, 'cancelled',  'failed',  8, 'drop_off'],
        ];

        foreach ($orders as [$number, $ci, $si, $qty, $status, $payStatus, $daysAgo, $fulfillment]) {
            if (Order::where('order_number', $number)->exists()) {
                continue;
            }

            $customer = $customers[$ci];
            $service = $services[$si];
            $subtotal = $service->price * $qty;
            $orderedAt = now()->subDays($daysAgo);

            $order = Order::create([
                'order_number' => $number,
                'user_id' => $customer->id,
                'status' => $status,
                'payment_status' => $payStatus,
                'fulfillment_type' => $fulfillment,
                'total' => $subtotal,
                'address' => $fulfillment === 'pickup_delivery' ? $customer->address : null,
                'scheduled_at' => $fulfillment === 'pickup_delivery' ? $orderedAt->copy()->addDay() : null,
                'notes' => 'Data demo',
                'ordered_at' => $orderedAt,
            ]);

            $order->items()->create([
                'service_id' => $service->id,
                'service_name' => $service->name,
                'unit_price' => $service->price,
                'quantity' => $qty,
                'subtotal' => $subtotal,
            ]);

            if (in_array($payStatus, ['paid', 'pending', 'failed'], true)) {
                Payment::create([
                    'order_id' => $order->id,
                    'method' => 'qris',
                    'provider' => 'demo',
                    'provider_transaction_id' => 'DEMO-' . $number,
                    'amount' => $subtotal,
                    'status' => $payStatus,
                    'paid_at' => $payStatus === 'paid' ? $orderedAt->copy()->addHour() : null,
                ]);
            }
        }
    }
}
