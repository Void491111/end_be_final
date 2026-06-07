<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $coffee = Category::where('slug', 'coffee')->first();
        $nonCoffee = Category::where('slug', 'non-coffee')->first();
        $food = Category::where('slug', 'food')->first();
        $snack = Category::where('slug', 'snack')->first();
        $dessert = Category::where('slug', 'dessert')->first();

        $menus = [
            // Coffee
            ['category_id' => $coffee->id, 'name' => 'Espresso', 'description' => 'Rich and bold', 'price' => 22000],
            ['category_id' => $coffee->id, 'name' => 'Cappuccino', 'description' => 'Espresso with steamed milk foam', 'price' => 28000],
            ['category_id' => $coffee->id, 'name' => 'Latte', 'description' => 'Smooth espresso with milk', 'price' => 30000],
            ['category_id' => $coffee->id, 'name' => 'Americano', 'description' => 'Espresso with hot water', 'price' => 25000],
            ['category_id' => $coffee->id, 'name' => 'Mocha', 'description' => 'Espresso, chocolate & milk', 'price' => 32000],
            ['category_id' => $coffee->id, 'name' => 'Macchiato', 'description' => 'Espresso with milk foam', 'price' => 28000],

            // Non-Coffee
            ['category_id' => $nonCoffee->id, 'name' => 'Matcha Latte', 'description' => 'Japanese green tea with milk', 'price' => 32000],
            ['category_id' => $nonCoffee->id, 'name' => 'Chocolate Frappe', 'description' => 'Blended iced chocolate', 'price' => 30000],
            ['category_id' => $nonCoffee->id, 'name' => 'Lemon Tea', 'description' => 'Fresh lemon iced tea', 'price' => 18000],

            // Food
            ['category_id' => $food->id, 'name' => 'Nasi Goreng', 'description' => 'Indonesian fried rice', 'price' => 28000],
            ['category_id' => $food->id, 'name' => 'Mie Goreng', 'description' => 'Indonesian fried noodles', 'price' => 25000],
            ['category_id' => $food->id, 'name' => 'Club Sandwich', 'description' => 'Triple-layered sandwich', 'price' => 32000],

            // Snack
            ['category_id' => $snack->id, 'name' => 'French Fries', 'description' => 'Crispy golden fries', 'price' => 20000],
            ['category_id' => $snack->id, 'name' => 'Onion Rings', 'description' => 'Battered onion rings', 'price' => 18000],

            // Dessert
            ['category_id' => $dessert->id, 'name' => 'Tiramisu', 'description' => 'Classic Italian dessert', 'price' => 35000],
            ['category_id' => $dessert->id, 'name' => 'Brownies', 'description' => 'Chocolate fudge brownies', 'price' => 22000],
        ];

        foreach ($menus as $menu) {
            Menu::create([
                ...$menu,
                'is_available' => true,
            ]);
        }
    }
}