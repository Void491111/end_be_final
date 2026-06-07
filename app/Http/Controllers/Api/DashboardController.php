<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Carbon\Carbon;

class DashboardController extends Controller
{
    // GET /api/dashboard/weekly-revenue
    // Return revenue per hari, Minggu - Sabtu (untuk bar chart di dashboard POS)
    public function weeklyRevenue()
    {
        $startOfWeek = now()->startOfWeek(Carbon::SUNDAY);
        $endOfWeek = $startOfWeek->copy()->endOfWeek(Carbon::SATURDAY);

        $orders = Order::where('status', 'completed')
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->selectRaw('DATE(created_at) as date, SUM(total) as revenue')
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $dayLabels = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        $result = [];
        $totalRevenue = 0;

        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            $dateKey = $date->toDateString();
            $revenue = (float) ($orders[$dateKey]->revenue ?? 0);
            $totalRevenue += $revenue;

            $result[] = [
                'day' => $dayLabels[$i],
                'date' => $dateKey,
                'revenue' => $revenue,
            ];
        }

        return response()->json([
            'total_revenue' => $totalRevenue,
            'week_start' => $startOfWeek->toDateString(),
            'week_end' => $endOfWeek->toDateString(),
            'data' => $result,
        ]);
    }
}