<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;

class RevenueChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): ?string
    {
        return match ($this->pageFilters['period'] ?? 'today') {
            '7d'  => 'Revenue 7 Hari Terakhir',
            '30d' => 'Revenue 30 Hari Terakhir',
            'all' => 'Revenue Semua Waktu',
            default => 'Revenue Hari Ini',
        };
    }

    protected function getData(): array
    {
        $end = now()->startOfDay();

        $start = match ($this->pageFilters['period'] ?? 'today') {
            '7d'  => now()->subDays(6)->startOfDay(),
            '30d' => now()->subDays(29)->startOfDay(),
            'all' => ($min = Order::min('created_at'))
                ? Carbon::parse($min)->startOfDay()
                : now()->subDays(6)->startOfDay(),
            default => $end->copy(), // hari ini
        };

        // Cap jumlah bucket harian biar chart tetap kebaca (max 30 titik).
        if ($start->diffInDays($end) > 30) {
            $start = $end->copy()->subDays(30);
        }

        $days = collect();
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $days->push($d->copy());
        }

        $revenues = $days->map(fn ($date) => Order::where('status', 'completed')
            ->whereDate('created_at', $date)
            ->sum('total'));

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