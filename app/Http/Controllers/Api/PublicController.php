<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Menu;
use App\Models\Table;
use Illuminate\Http\Request;

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
}