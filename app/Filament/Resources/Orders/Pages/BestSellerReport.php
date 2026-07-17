<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;

class BestSellerReport extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFire;

    protected static ?string $navigationLabel = 'Laporan Best Seller';

    protected static ?int $navigationSort = 90;

    protected string $view = 'filament.pages.best-seller-report';

    public string $period = 'all';

    public array $bestSellers = [];

    public function mount(): void
    {
        $this->loadReport();
    }

    public function updatedPeriod(): void
    {
        $this->loadReport();
    }

    public function loadReport(): void
    {
        $query = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('menus', 'menus.id', '=', 'order_items.menu_id')
            ->join('categories', 'categories.id', '=', 'menus.category_id')
            ->where('orders.status', 'completed');

        if ($this->period === '7d') $query->where('orders.created_at', '>=', now()->subDays(7));
        if ($this->period === '30d') $query->where('orders.created_at', '>=', now()->subDays(30));

        $this->bestSellers = $query
            ->select(
                'menus.name',
                'categories.name as category_name',
                DB::raw('SUM(order_items.quantity) as total_qty'),
                DB::raw('SUM(order_items.subtotal) as total_revenue'),
                DB::raw('COUNT(DISTINCT orders.id) as order_count')
            )
            ->groupBy('menus.id', 'menus.name', 'categories.name')
            ->orderByDesc('total_qty')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->toArray();
    }
}