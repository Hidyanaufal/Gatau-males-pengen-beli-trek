<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrderStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $revenue = (float) Payment::where('status', PaymentStatus::Paid->value)->sum('amount');

        return [
            Stat::make('Total pesanan', Order::count())
                ->description('Semua pesanan')
                ->color('primary'),

            Stat::make('Pesanan baru', Order::where('status', OrderStatus::Pending->value)->count())
                ->description('Menunggu konfirmasi')
                ->color('gray'),

            Stat::make('Sedang diproses', Order::whereIn('status', [
                OrderStatus::Confirmed->value,
                OrderStatus::Processing->value,
            ])->count())
                ->description('Dikonfirmasi dan diproses')
                ->color('warning'),

            Stat::make('Pesanan selesai', Order::where('status', OrderStatus::Completed->value)->count())
                ->description('Sudah diselesaikan')
                ->color('success'),

            Stat::make('Pendapatan terverifikasi', 'Rp ' . number_format($revenue, 0, ',', '.'))
                ->description('Hanya dari pembayaran lunas')
                ->color('primary'),
        ];
    }
}
