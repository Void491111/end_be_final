<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    // POST /api/orders
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_type' => ['required', Rule::in(['dine_in', 'takeaway'])],
            'items' => 'required|array|min:1',
            'items.*.menu_id' => 'required|exists:menus,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $subtotal = 0;
            $itemsData = [];

            foreach ($validated['items'] as $item) {
                $menu = Menu::find($item['menu_id']);

                if (! $menu->is_available) {
                    abort(422, "{$menu->name} sedang tidak tersedia.");
                }

                $itemSubtotal = $menu->price * $item['quantity'];
                $subtotal += $itemSubtotal;

                $itemsData[] = [
                    'menu_id' => $menu->id,
                    'menu_name_snapshot' => $menu->name,
                    'price_snapshot' => $menu->price,
                    'quantity' => $item['quantity'],
                    'subtotal' => $itemSubtotal,
                ];
            }

            $tax = $subtotal * 0.10; // 10% tax
            $total = $subtotal + $tax;

            $order = Order::create([
                'queue_number' => Order::generateQueueNumber(),
                'user_id' => $request->user()->id,
                'order_type' => $validated['order_type'],
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'payment_method' => 'cash',
                'status' => 'completed',
            ]);

            $order->items()->createMany($itemsData);

            return response()->json($order->load('items', 'user:id,name'), 201);
        });
    }

    // GET /api/orders
    public function index(Request $request)
    {
        $query = Order::with(['items', 'user:id,name'])
            ->orderBy('created_at', 'desc');

        // Filter by period
        if ($period = $request->input('period')) {
            $this->applyPeriodFilter($query, $period);
        }

        // Filter by status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Search by queue number
        if ($search = $request->input('search')) {
            $query->where('queue_number', 'like', "%{$search}%");
        }

        return $query->paginate(20);
    }

    // GET /api/orders/{order}
    public function show(Order $order)
    {
        return $order->load(['items', 'user:id,name']);
    }

    // PATCH /api/orders/{order}/void
    public function void(Request $request, Order $order)
    {
        $validated = $request->validate([
            'voided_reason' => 'required|string|min:3',
        ]);

        if ($order->status === 'voided') {
            abort(422, 'Order sudah di-void sebelumnya.');
        }

        $order->update([
            'status' => 'voided',
            'voided_at' => now(),
            'voided_reason' => $validated['voided_reason'],
        ]);

        return response()->json($order->fresh('items'));
    }

    // GET /api/orders/stats
    public function stats(Request $request)
    {
        $query = Order::query();

        if ($period = $request->input('period', 'today')) {
            $this->applyPeriodFilter($query, $period);
        }

        $completed = (clone $query)->where('status', 'completed');
        $voided = (clone $query)->where('status', 'voided');

        return response()->json([
            'total_orders' => $completed->count(),
            'total_revenue' => (float) $completed->sum('total'),
            'avg_order' => (float) ($completed->avg('total') ?? 0),
            'voided_count' => $voided->count(),
            'voided_amount' => (float) $voided->sum('total'),
        ]);
    }

    // Helper: apply period filter
    private function applyPeriodFilter($query, string $period): void
    {
        match ($period) {
            'today' => $query->whereDate('created_at', today()),
            '7d' => $query->where('created_at', '>=', now()->subDays(7)),
            '30d' => $query->where('created_at', '>=', now()->subDays(30)),
            '90d' => $query->where('created_at', '>=', now()->subDays(90)),
            default => null,
        };
    }
}