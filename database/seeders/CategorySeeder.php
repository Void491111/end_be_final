<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Coffee', 'icon' => 'coffee', 'sort_order' => 1],
            ['name' => 'Non-Coffee', 'icon' => 'cup', 'sort_order' => 2],
            ['name' => 'Food', 'icon' => 'food', 'sort_order' => 3],
            ['name' => 'Snack', 'icon' => 'cookie', 'sort_order' => 4],
            ['name' => 'Dessert', 'icon' => 'ice-cream', 'sort_order' => 5],
        ];

        foreach ($categories as $category) {
            Category::create([
                ...$category,
                'is_active' => true,
            ]);
        }
    }
}