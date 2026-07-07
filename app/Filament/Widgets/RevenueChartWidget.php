<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class RevenueChartWidget extends ChartWidget
{
    protected ?string $heading = 'Revenue 7 Hari Terakhir';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $days = collect(range(6, 0))->map(fn ($i) => Carbon::today()->subDays($i));

        $revenues = $days->map(function ($date) {
            return Order::where('status', 'completed')
                ->whereDate('created_at', $date)
                ->sum('total');
        });

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (Rp)',
                    'data' => $revenues->toArray(),
                    'borderColor' => '#F59E0B',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $days->map(fn ($d) => $d->format('d M'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}