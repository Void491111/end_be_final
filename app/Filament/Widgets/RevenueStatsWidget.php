<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RevenueStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $todayRevenue = Order::where('status', 'completed')
            ->whereDate('created_at', today())
            ->sum('total');

        $todayOrders = Order::whereDate('created_at', today())->count();

        $avgOrder = $todayOrders > 0 ? $todayRevenue / $todayOrders : 0;

        $voidedToday = Order::where('status', 'voided')
            ->whereDate('created_at', today())
            ->count();

        return [
            Stat::make('Revenue Hari Ini', 'Rp ' . number_format($todayRevenue, 0, ',', '.'))
                ->description('Total pendapatan hari ini')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Total Order', $todayOrders)
                ->description('Jumlah pesanan hari ini')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('primary'),

            Stat::make('Rata-rata Order', 'Rp ' . number_format($avgOrder, 0, ',', '.'))
                ->description('Average order value')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('info'),

            Stat::make('Order Voided', $voidedToday)
                ->description('Order dibatalkan hari ini')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
        ];
    }
}