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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_type' => ['required', Rule::in(['dine_in', 'takeaway'])],
            'items' => 'required|array|min:1',
            'items.*.menu_id' => 'required|exists:menus,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.ice_level' => 'nullable|string|max:30',
            'items.*.sugar_level' => 'nullable|string|max:30',
            'items.*.notes' => 'nullable|string|max:200',
        ]);

        return Order::withQueueNumberRetry(fn () => DB::transaction(function () use ($validated, $request) {
            // Ambil semua menu sekali, jangan Menu::find() per item di dalam loop.
            $menus = Menu::whereIn('id', array_unique(array_column($validated['items'], 'menu_id')))
                ->get(['id', 'name', 'price', 'is_available'])
                ->keyBy('id');

            $subtotal = 0;
            $itemsData = [];

            foreach ($validated['items'] as $item) {
                $menu = $menus->get($item['menu_id']);

                if (! $menu) {
                    abort(422, 'Menu tidak ditemukan.');
                }

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

            $tax = $subtotal * 0.10;
            $total = $subtotal + $tax;

            $order = Order::create([
                'queue_number' => Order::generateQueueNumber(),
                'user_id' => $request->user()->id,
                'source' => 'cashier',
                'table_id' => null,
                'order_type' => $validated['order_type'],
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'payment_method' => 'cash',
                'payment_status' => 'settlement',
                'paid_at' => now(),
                'status' => 'completed',
                'voided_reason' => $this->composeCashierNotes($validated['items'], $menus),
            ]);

            $order->items()->createMany($itemsData);

            return response()->json($order->load('items', 'user:id,name'), 201);
        }));
    }

    private function composeCashierNotes(array $items, $menus): ?string
    {
        $result = collect($items)
            ->map(function ($i) use ($menus) {
                $parts = [];

                // "Normal" = default, gak perlu ditulis
                foreach (['ice_level', 'sugar_level'] as $key) {
                    $val = trim((string) ($i[$key] ?? ''));
                    if ($val !== '' && strcasecmp($val, 'Normal') !== 0) {
                        $parts[] = $val;
                    }
                }

                $note = trim((string) ($i['notes'] ?? ''));
                if ($note !== '') {
                    $parts[] = $note;
                }

                if (empty($parts)) {
                    return null;
                }

                $name = $menus->get($i['menu_id'])?->name ?? 'Item';

                return $name . ' x' . $i['quantity'] . ': ' . implode(', ', $parts);
            })
            ->filter()
            ->implode(' | ');

        return $result ?: null;
    }

    /**
     * Order kasir HARI INI buat slider POS. Cuma baca data existing.
     */
    public function cashierRecent(Request $request)
    {
        $limit = min((int) $request->input('limit', 20), 50);

        $orders = Order::with('items')
            ->where('source', 'cashier')
            ->where('status', 'completed')
            ->whereDate('created_at', today())
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($o) => [
                'id'           => $o->id,
                'queue_number' => $o->queue_number,
                'order_type'   => $o->order_type,
                'total'        => (float) $o->total,
                'created_at'   => $o->created_at,
                'custom'       => $o->voided_reason,
                'items'        => $o->items->map(fn ($it) => [
                    'name'     => $it->menu_name_snapshot,
                    'quantity' => $it->quantity,
                ]),
            ]);

        return response()->json(['data' => $orders]);
    }

    public function index(Request $request)
    {
        $query = Order::with(['items', 'user:id,name', 'table:id,code,name'])
            ->orderBy('created_at', 'desc');

        if ($period = $request->input('period')) {
            $this->applyPeriodFilter($query, $period);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($source = $request->input('source')) {
            $query->where('source', $source);
        }

        if ($search = $request->input('search')) {
            $query->where('queue_number', 'like', "%{$search}%");
        }

        return $query->paginate(20);
    }

    public function show(Order $order)
    {
        return $order->load(['items', 'user:id,name', 'table:id,code,name']);
    }

    public function void(Request $request, Order $order)
    {
        $validated = $request->validate([
            'voided_reason' => 'required|string|min:3',
        ]);

        if (in_array($order->status, ['voided', 'expired'])) {
            abort(422, 'Order sudah tidak aktif.');
        }

        $order->update([
            'status' => 'voided',
            'voided_at' => now(),
            'voided_reason' => $validated['voided_reason'],
        ]);

        return response()->json($order->fresh('items'));
    }

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

    // ── Batch 7: Cashier QR Queue ──────────────────────────────

    public function queue()
    {
        // Aktif (belum kelar): FIFO by created_at = urutan pesanan masuk
        $active = Order::with(['items', 'table'])
            ->where('source', 'customer_qr')
            ->whereIn('status', ['paid', 'preparing'])
            ->orderBy('created_at')
            ->get();

        // Selesai HARI INI: tetap ditampilkan biar kasir liat mana yg udah kelar.
        // Pakai updated_at karena kolom completed_at sengaja gak ada di schema.
        $doneToday = Order::with(['items', 'table'])
            ->where('source', 'customer_qr')
            ->where('status', 'completed')
            ->whereDate('updated_at', today())
            ->orderByDesc('updated_at')
            ->get();

        $orders = $active->concat($doneToday)
            ->map(fn ($o) => $this->transformQueueOrder($o));

        return response()->json(['data' => $orders]);
    }

    public function queueCount()
    {
        $count = Order::where('source', 'customer_qr')
            ->where('status', 'paid')
            ->count();

        return response()->json(['count' => $count]);
    }

    public function confirm(Order $order)
    {
        if ($order->source !== 'customer_qr' || $order->status !== 'paid') {
            return response()->json(['message' => 'Order tidak bisa dikonfirmasi'], 409);
        }
        $order->update(['status' => 'preparing']);
        return response()->json(['data' => $this->transformQueueOrder($order->fresh(['items', 'table']))]);
    }

    public function complete(Order $order)
    {
        if ($order->source !== 'customer_qr' || $order->status !== 'preparing') {
            return response()->json(['message' => 'Order belum siap diselesaikan'], 409);
        }
        $order->update(['status' => 'completed']); // GA ADA completed_at — jangan tambah
        return response()->json(['data' => $this->transformQueueOrder($order->fresh(['items', 'table']))]);
    }

    public function reject(Request $request, Order $order)
    {
        $data = $request->validate(['reason' => 'required|string|max:255']);
        if ($order->source !== 'customer_qr' || !in_array($order->status, ['paid', 'preparing'])) {
            return response()->json(['message' => 'Order tidak bisa ditolak'], 409);
        }
        $order->update([
            'status' => 'voided',
            'voided_at' => now(),
            'voided_reason' => $data['reason'],
        ]);
        return response()->json(['data' => $this->transformQueueOrder($order->fresh(['items', 'table']))]);
    }

    private function transformQueueOrder(Order $o): array
    {
        return [
            'id'            => $o->id,
            'queue_number'  => $o->queue_number,
            'table_code'    => $o->table?->code,
            'customer_name' => $o->customer_name,
            'status'        => $o->status,
            'total'         => (float) $o->total,
            'notes'         => $o->voided_reason, // catatan customer (per-item notes hack)
            'paid_at'       => $o->paid_at,
            'created_at'    => $o->created_at,
            'items'         => $o->items->map(fn ($it) => [
                'name'     => $it->menu_name_snapshot,
                'quantity' => $it->quantity,
            ]),
        ];
    }
}