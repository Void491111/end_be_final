<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        $query = Menu::with('category:id,name,slug')
            ->select(['id', 'category_id', 'name', 'description', 'price', 'image', 'is_available']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->boolean('available_only')) {
            $query->where('is_available', true);
        }

        return $query->orderBy('name')->get();
    }

    public function toggleAvailability(Menu $menu)
    {
        $menu->update([
            'is_available' => ! $menu->is_available,
        ]);

        return response()->json([
            'id' => $menu->id,
            'is_available' => $menu->is_available,
        ]);
    }

    // GET /api/menus/recommendations (auth) atau /api/public/recommendations (no auth)
    // Query param: limit (default 5), exclude (comma-separated menu IDs)
    public function recommendations(Request $request)
        {
            $limit = (int) $request->input('limit', 5);
            $excludeIds = $request->filled('exclude')
                ? array_map('intval', explode(',', $request->input('exclude')))
                : [];

            $bestSellers = $this->getBestSellerMap();

            if (empty($bestSellers)) {
                return response()->json(['data' => []]);
            }

            $menus = $this->fetchMenusByIds(array_keys($bestSellers), $excludeIds, $limit);

            return response()->json([
                'data' => $menus->map(fn ($m) => $this->transformMenu($m, $bestSellers))->values(),
            ]);
        }

        private function getBestSellerMap(): array
        {
            return DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.status', 'completed')
                ->select('order_items.menu_id', DB::raw('SUM(order_items.quantity) as total_sold'))
                ->groupBy('order_items.menu_id')
                ->orderByDesc('total_sold')
                ->pluck('total_sold', 'menu_id')
                ->toArray();
        }

        private function fetchMenusByIds(array $ids, array $excludeIds, int $limit)
        {
            return Menu::with('category:id,name,slug')
                ->where('is_available', true)
                ->whereIn('id', array_slice($ids, 0, $limit * 2))
                ->whereNotIn('id', $excludeIds)
                ->get()
                ->sortByDesc(fn ($m) => $this->getBestSellerMap()[$m->id] ?? 0)
                ->take($limit);
        }

        private function transformMenu($menu, array $bestSellers): array
        {
            return [
                'id' => $menu->id,
                'name' => $menu->name,
                'description' => $menu->description,
                'price' => $menu->price,
                'image' => $menu->image,
                'imgeUrl' => $menu->imageUrl,
                'category' => $menu->category,
                'total_sold' => $bestSellers[$menu->id] ?? 0,
            ];
        }
}