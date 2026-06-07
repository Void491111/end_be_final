<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        $query = Menu::with('category:id,name,slug')
            ->select(['id', 'category_id', 'name', 'description', 'price', 'image', 'is_available']);

        // Optional filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Optional filter: only available menus (for POS)
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
}