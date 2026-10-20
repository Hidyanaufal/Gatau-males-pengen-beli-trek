<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Collection;

class OrderStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    // Kartu menyegarkan diri tiap 30 detik, jadi pesanan baru dari HP langsung terlihat
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $revenue = (float) Payment::where('status', PaymentStatus::Paid->value)->sum('amount');

        // Data grafik mini: 7 hari terakhir (hari ini di ujung kanan)
        $allOrders = $this->dailyOrders(Order::query());
        $pending = $this->dailyOrders(Order::where('status', OrderStatus::Pending->value));
        $active = $this->dailyOrders(Order::whereIn('status', [
            OrderStatus::Confirmed->value,
            OrderStatus::Processing->value,
        ]));
        $done = $this->dailyOrders(Order::where('status', OrderStatus::Completed->value));
        $money = $this->dailyRevenue();

        return [
            Stat::make('Total pesanan', Order::count())
                ->description(array_sum($allOrders) . ' pesanan dalam 7 hari terakhir')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart($allOrders)
                ->color('primary'),

            Stat::make('Pesanan baru', Order::where('status', OrderStatus::Pending->value)->count())
                ->description('Menunggu konfirmasi')
                ->descriptionIcon('heroicon-m-clock')
                ->chart($pending)
                ->color('gray'),

            Stat::make('Sedang diproses', Order::whereIn('status', [
                OrderStatus::Confirmed->value,
                OrderStatus::Processing->value,
            ])->count())
                ->description('Dikonfirmasi dan diproses')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->chart($active)
                ->color('warning'),

            Stat::make('Pesanan selesai', Order::where('status', OrderStatus::Completed->value)->count())
                ->description('Sudah diselesaikan')
                ->descriptionIcon('heroicon-m-check-circle')
                ->chart($done)
                ->color('success'),

            Stat::make('Pendapatan terverifikasi', 'Rp ' . number_format($revenue, 0, ',', '.'))
                ->description('Hari ini: Rp ' . number_format(end($money) ?: 0, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->chart($money)
                ->color('primary'),
        ];
    }

    /** Jumlah pesanan per hari (berdasarkan tanggal dibuat) selama 7 hari terakhir. */
    private function dailyOrders($query): array
    {
        $rows = $query
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->groupBy('d')
            ->pluck('c', 'd');

        return $this->fillWeek($rows);
    }

    /** Pendapatan per hari dari pembayaran LUNAS selama 7 hari terakhir. */
    private function dailyRevenue(): array
    {
        $rows = Payment::where('status', PaymentStatus::Paid->value)
            ->whereRaw('COALESCE(paid_at, created_at) >= ?', [now()->subDays(6)->startOfDay()])
            ->selectRaw('DATE(COALESCE(paid_at, created_at)) as d, SUM(amount) as c')
            ->groupBy('d')
            ->pluck('c', 'd');

        return $this->fillWeek($rows);
    }

    /** Menyusun 7 angka berurutan; hari tanpa data diisi 0. */
    private function fillWeek(Collection $rows): array
    {
        $out = [];

        for ($i = 6; $i >= 0; $i--) {
            $out[] = (float) ($rows[now()->subDays($i)->toDateString()] ?? 0);
        }

        return $out;
    }
}