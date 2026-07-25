<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RevenueStatsWidget extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    /**
     * Resolve periode dari filter dashboard.
     * @return array{0: ?\Illuminate\Support\Carbon, 1: string}
     */
    protected function periodRange(): array
    {
        return match ($this->pageFilters['period'] ?? 'today') {
            '7d'  => [now()->subDays(6)->startOfDay(), '7 Hari Terakhir'],
            '30d' => [now()->subDays(29)->startOfDay(), '30 Hari Terakhir'],
            'all' => [null, 'Semua Waktu'],
            default => [now()->startOfDay(), 'Hari Ini'],
        };
    }

    protected function getStats(): array
    {
        [$start, $label] = $this->periodRange();

        // helper: apply lower-bound tanggal kalau ada (null = semua waktu)
        $inRange = fn ($query) => $query->when($start, fn ($q) => $q->where('created_at', '>=', $start));

        $revenue = $inRange(Order::where('status', 'completed'))->sum('total');
        $orders  = $inRange(Order::query())->count();
        $avgOrder = $orders > 0 ? $revenue / $orders : 0;
        $voided  = $inRange(Order::where('status', 'voided'))->count();

        return [
            Stat::make('Revenue', 'Rp ' . number_format($revenue, 0, ',', '.'))
                ->description('Total pendapatan · ' . $label)
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Total Order', $orders)
                ->description('Jumlah pesanan · ' . $label)
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('primary'),

            Stat::make('Rata-rata Order', 'Rp ' . number_format($avgOrder, 0, ',', '.'))
                ->description('Average order value · ' . $label)
                ->descriptionIcon('heroicon-m-calculator')
                ->color('info'),

            Stat::make('Order Voided', $voided)
                ->description('Order dibatalkan · ' . $label)
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
        ];
    }
}