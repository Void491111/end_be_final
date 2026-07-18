<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Order;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Public endpoints untuk customer QR flow: submit order + polling status
class PublicOrderController extends Controller
{
    // POST /api/public/orders
    public function store(Request $request)
    {
        $validated = $request->validate([
            'table_code' => 'required|string|exists:tables,code',
            'customer_name' => 'required|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.menu_id' => 'required|exists:menus,id',
            'items.*.quantity' => 'required|integer|min:1|max:20',
            'items.*.notes' => 'nullable|string|max:200',
        ]);

        return DB::transaction(function () use ($validated) {
            $table = Table::where('code', $validated['table_code'])->first();

            if (!$table->is_active) {
                abort(403, 'Meja tidak aktif');
            }

            [$subtotal, $itemsData] = $this->buildItems($validated['items']);
            $tax = $subtotal * 0.10;
            $notes = $this->composeNotes($validated['items']);

            $order = Order::create([
                'queue_number' => Order::generateQueueNumber(),
                'user_id' => null,
                'source' => 'customer_qr',
                'table_id' => $table->id,
                'customer_name' => $validated['customer_name'],
                'order_type' => 'dine_in',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $subtotal + $tax,
                'payment_method' => 'cash', // placeholder — Batch 6 ganti Midtrans
                'payment_status' => 'pending',
                'paid_at' => null,
                'status' => 'pending_payment',
                'voided_reason' => $notes ?: null, // hack sementara buat notes per-item
            ]);

            $order->items()->createMany($itemsData);

            return response()->json($this->orderPayload($order->fresh(['items', 'table']), 'Pesanan berhasil dibuat'), 201);
        });
    }

    // GET /api/public/orders/{id}/status
    public function status(int $id)
    {
        $order = Order::where('source', 'customer_qr')
            ->with('table:id,code,name', 'items')
            ->find($id);

        if (!$order) {
            return response()->json(['message' => 'Pesanan tidak ditemukan'], 404);
        }

        return response()->json($this->orderPayload($order));
    }

    private function buildItems(array $items): array
    {
        $subtotal = 0;
        $data = [];

        foreach ($items as $item) {
            $menu = Menu::find($item['menu_id']);

            if (!$menu->is_available) {
                abort(422, "{$menu->name} sedang tidak tersedia.");
            }

            $lineSubtotal = $menu->price * $item['quantity'];
            $subtotal += $lineSubtotal;

            $data[] = [
                'menu_id' => $menu->id,
                'menu_name_snapshot' => $menu->name,
                'price_snapshot' => $menu->price,
                'quantity' => $item['quantity'],
                'subtotal' => $lineSubtotal,
            ];
        }

        return [$subtotal, $data];
    }

    private function composeNotes(array $items): string
    {
        return collect($items)
            ->filter(fn ($i) => !empty($i['notes'] ?? null))
            ->map(fn ($i) => Menu::find($i['menu_id'])->name . ': ' . $i['notes'])
            ->implode(' | ');
    }

    private function orderPayload(Order $order, ?string $message = null): array
    {
        $payload = [
            'id' => $order->id,
            'queue_number' => $order->queue_number,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'subtotal' => $order->subtotal,
            'tax' => $order->tax,
            'total' => $order->total,
            'customer_name' => $order->customer_name,
            'table' => $order->table ? [
                'code' => $order->table->code,
                'name' => $order->table->name,
            ] : null,
            'items' => $order->items->map(fn ($i) => [
                'menu_name' => $i->menu_name_snapshot,
                'quantity' => $i->quantity,
                'subtotal' => $i->subtotal,
            ]),
            'created_at' => $order->created_at,
            'paid_at' => $order->paid_at,
        ];

        if ($message) {
            $payload['message'] = $message;
        }

        return $payload;
    }
}
