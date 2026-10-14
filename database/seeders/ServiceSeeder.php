<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            [
                'name' => 'Cuci Kiloan Reguler',
                'description' => 'Cuci dan lipat, selesai 2 hari.',
                'price' => 7000,
                'unit' => 'kg',
                'estimated_hours' => 48,
            ],
            [
                'name' => 'Cuci Kiloan Express',
                'description' => 'Cuci dan lipat, selesai hari yang sama.',
                'price' => 12000,
                'unit' => 'kg',
                'estimated_hours' => 8,
            ],
            [
                'name' => 'Cuci Setrika',
                'description' => 'Cuci, setrika, dan lipat.',
                'price' => 9000,
                'unit' => 'kg',
                'estimated_hours' => 48,
            ],
            [
                'name' => 'Cuci Selimut',
                'description' => 'Selimut ukuran standar.',
                'price' => 25000,
                'unit' => 'item',
                'estimated_hours' => 72,
            ],
        ];

        foreach ($services as $service) {
            Service::updateOrCreate(['name' => $service['name']], $service);
        }
    }
}