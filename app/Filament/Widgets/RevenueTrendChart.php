<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class RevenueTrendChart extends ChartWidget
{
    // Urutan di dashboard (makin kecil makin atas). Ubah angkanya kalau mau dipindah.
    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    protected ?string $maxHeight = '320px';

    public function getHeading(): ?string
    {
        return 'Tren pendapatan';
    }

    public function getDescription(): ?string
    {
        $year = $this->selectedYear();
        $totals = $this->monthlyTotals($year);

        $sum = array_sum(array_filter($totals, fn ($v) => $v !== null));
        $text = 'Total ' . $year . ': Rp' . number_format($sum, 0, ',', '.');

        $trend = $this->trend($year, $totals);
        if ($trend !== null) {
            $text .= ' · Bulan ini ' . ($trend >= 0 ? '▲ ' : '▼ ')
                . number_format(abs($trend), 1, ',', '.') . '% dibanding bulan lalu';
        }

        return $text;
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        $now = now()->year;

        $first = (int) (Payment::query()
            ->selectRaw('MIN(YEAR(COALESCE(paid_at, created_at))) as y')
            ->value('y') ?: $now);

        $years = [];
        for ($y = $now; $y >= min($first, $now); $y--) {
            $years[(string) $y] = (string) $y;
        }

        return $years;
    }

    protected function getData(): array
    {
        $year = $this->selectedYear();
        $totals = $this->monthlyTotals($year);

        // Hijau kalau naik (atau belum ada pembanding), merah kalau turun
        $trend = $this->trend($year, $totals);
        $up = $trend === null || $trend >= 0;
        $line = $up ? '#16a34a' : '#dc2626';
        $fill = $up ? 'rgba(22, 163, 74, 0.15)' : 'rgba(220, 38, 38, 0.15)';

        return [
            'datasets' => [
                [
                    'label' => 'Pendapatan (Rp)',
                    'data' => $totals,
                    'borderColor' => $line,
                    'backgroundColor' => $fill,
                    'pointBackgroundColor' => $line,
                    'fill' => true,
                    'tension' => 0.4,
                    'borderWidth' => 3,
                    'pointRadius' => 4,
                ],
            ],
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
        ];
    }

    protected function getOptions(): array | RawJs | null
    {
        return RawJs::make(<<<'JS'
        {
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (ctx) => 'Rp' + Number(ctx.parsed.y).toLocaleString('id-ID'),
                    },
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: (v) => 'Rp' + Number(v).toLocaleString('id-ID') },
                },
                x: { grid: { display: false } },
            },
        }
        JS);
    }

    private function selectedYear(): int
    {
        return (int) ($this->filter ?: now()->year);
    }

    /**
     * Pendapatan per bulan (12 angka) dari pembayaran yang LUNAS saja.
     * Bulan yang belum terjadi di tahun berjalan diisi null supaya garis tidak jatuh ke nol.
     */
    private function monthlyTotals(int $year): array
    {
        $rows = Payment::query()
            ->where('status', PaymentStatus::Paid->value)
            ->whereRaw('YEAR(COALESCE(paid_at, created_at)) = ?', [$year])
            ->selectRaw('MONTH(COALESCE(paid_at, created_at)) as bulan, SUM(amount) as total')
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        $out = [];
        for ($m = 1; $m <= 12; $m++) {
            $future = $year === now()->year && $m > now()->month;
            $out[] = $future ? null : (float) ($rows[$m] ?? 0);
        }

        return $out;
    }

    /** Persen perubahan bulan ini dibanding bulan lalu (hanya tahun berjalan). */
    private function trend(int $year, array $totals): ?float
    {
        if ($year !== now()->year || now()->month < 2) {
            return null;
        }

        $current = $totals[now()->month - 1] ?? 0;
        $previous = $totals[now()->month - 2] ?? 0;

        if (! $previous) {
            return null;
        }

        return (($current - $previous) / $previous) * 100;
    }
}