<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Menu;
use App\Models\Order;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicController extends Controller
{
    // GET /api/public/tables/{code}
    public function validateTable(string $code)
    {
        $table = Table::where('code', $code)->first();

        if (!$table) {
            return response()->json(['message' => 'Meja tidak ditemukan'], 404);
        }

        if (!$table->is_active) {
            return response()->json(['message' => 'Meja tidak aktif'], 403);
        }

        return response()->json([
            'id' => $table->id,
            'code' => $table->code,
            'name' => $table->name,
        ]);
    }

    // GET /api/public/menus
    public function menus(Request $request)
    {
        return Menu::with('category:id,name,slug')
            ->where('is_available', true)
            ->select(['id', 'category_id', 'name', 'description', 'price', 'image', 'is_available'])
            ->orderBy('name')
            ->get();
    }

    // GET /api/public/categories
    public function categories()
    {
        return Category::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug', 'icon']);
    }

    // POST /api/public/orders
    // Customer submit order dari QR meja
    // Status flow (pre-Midtrans): status=pending_payment, payment_status=pending, payment_method=cash
    // Nanti Batch 6 (Midtrans) ganti payment_method jadi qris_midtrans + webhook update ke settlement
    public function createOrder(Request $request)
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
            // Resolve table
            $table = Table::where('code', $validated['table_code'])->first();

            if (!$table->is_active) {
                abort(403, 'Meja tidak aktif');
            }

            // Hitung subtotal + validate ketersediaan
            $subtotal = 0;
            $itemsData = [];

            foreach ($validated['items'] as $item) {
                $menu = Menu::find($item['menu_id']);

                if (!$menu->is_available) {
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
                    // notes disimpan ke order-level buat MVP; kalau mau per-item bikin kolom baru
                ];
            }

            $tax = $subtotal * 0.10;
            $total = $subtotal + $tax;

            // Compose notes gabungan (per-item notes concat) — nanti bisa dipindah ke kolom order_item.notes kalau perlu
            $itemsWithNotes = collect($validated['items'])
                ->filter(fn($i) => !empty($i['notes'] ?? null))
                ->map(fn($i) => Menu::find($i['menu_id'])->name . ': ' . $i['notes'])
                ->implode(' | ');

            $order = Order::create([
                'queue_number' => Order::generateQueueNumber(),
                'user_id' => null,
                'source' => 'customer_qr',
                'table_id' => $table->id,
                'customer_name' => $validated['customer_name'],
                'order_type' => 'dine_in',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'payment_method' => 'cash', // placeholder — Midtrans di Batch 6
                'payment_status' => 'pending',
                'paid_at' => null,
                'status' => 'pending_payment',
                'voided_reason' => $itemsWithNotes ?: null, // sementara nyantol di voided_reason biar gak nambah kolom
            ]);

            $order->items()->createMany($itemsData);

            return response()->json([
                'id' => $order->id,
                'queue_number' => $order->queue_number,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'subtotal' => $order->subtotal,
                'tax' => $order->tax,
                'total' => $order->total,
                'table' => [
                    'code' => $table->code,
                    'name' => $table->name,
                ],
                'items' => $order->items->map(fn($i) => [
                    'menu_name' => $i->menu_name_snapshot,
                    'quantity' => $i->quantity,
                    'subtotal' => $i->subtotal,
                ]),
                'message' => 'Pesanan berhasil dibuat',
            ], 201);
        });
    }

    // GET /api/public/orders/{id}/status
    // Buat polling dari halaman status customer (Batch 5C pake ini)
    public function orderStatus(int $id)
    {
        $order = Order::where('source', 'customer_qr')
            ->with('table:id,code,name', 'items')
            ->find($id);

        if (!$order) {
            return response()->json(['message' => 'Pesanan tidak ditemukan'], 404);
        }

        return response()->json([
            'id' => $order->id,
            'queue_number' => $order->queue_number,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'total' => $order->total,
            'customer_name' => $order->customer_name,
            'table' => $order->table ? [
                'code' => $order->table->code,
                'name' => $order->table->name,
            ] : null,
            'items' => $order->items->map(fn($i) => [
                'menu_name' => $i->menu_name_snapshot,
                'quantity' => $i->quantity,
                'subtotal' => $i->subtotal,
            ]),
            'created_at' => $order->created_at,
            'paid_at' => $order->paid_at,
        ]);
    }
}
