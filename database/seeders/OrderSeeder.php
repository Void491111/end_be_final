<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $kasir = User::where('role', 'cashier')->first() ?? User::first();
        if (!$kasir) {
            $this->command->error('No user found. Run UserSeeder first.');
            return;
        }

        $menuWeights = [
            'Cappuccino'       => 40,
            'Latte'            => 25,
            'Nasi Goreng'      => 18,
            'Tiramisu'         => 15,
            'French Fries'     => 12,
            'Espresso'         => 10,
            'Americano'        => 8,
            'Matcha Latte'     => 7,
            'Mie Goreng'       => 6,
            'Chocolate Frappe' => 5,
            'Mocha'            => 4,
            'Club Sandwich'    => 4,
            'Brownies'         => 3,
            'Macchiato'        => 3,
            'Lemon Tea'        => 3,
            'Onion Rings'      => 2,
        ];

        $menus = Menu::whereIn('name', array_keys($menuWeights))->get()->keyBy('name');
        $weightedPool = [];
        foreach ($menuWeights as $name => $weight) {
            if ($menus->has($name)) {
                for ($i = 0; $i < $weight; $i++) {
                    $weightedPool[] = $menus[$name];
                }
            }
        }

        if (empty($weightedPool)) {
            $this->command->error('No matching menu found. Run MenuSeeder first.');
            return;
        }

        $totalOrders = 50;
        $this->command->info("Generating {$totalOrders} weighted orders...");

        $lastOrder = Order::latest('id')->first();
        $nextQueueNumber = $lastOrder ? ((int) substr($lastOrder->queue_number ?? 'A000', 1)) + 1 : 1;

        for ($i = 0; $i < $totalOrders; $i++) {
            $orderType = fake()->randomElement(['dine_in', 'takeaway']);
            $itemCount = fake()->numberBetween(1, 4);

            $subtotal = 0;
            $items = [];
            $usedMenuIds = [];

            for ($j = 0; $j < $itemCount; $j++) {
                $menu = $weightedPool[array_rand($weightedPool)];
                if (in_array($menu->id, $usedMenuIds)) continue;
                $usedMenuIds[] = $menu->id;

                $qty = fake()->numberBetween(1, 3);
                $itemSubtotal = $menu->price * $qty;
                $subtotal += $itemSubtotal;

                $items[] = [
                    'menu_id' => $menu->id,
                    'menu_name_snapshot' => $menu->name,
                    'price_snapshot' => $menu->price,
                    'quantity' => $qty,
                    'subtotal' => $itemSubtotal,
                ];
            }

            $tax = $subtotal * 0.10;
            $total = $subtotal + $tax;

            $createdAt = fake()->dateTimeBetween('-30 days', 'now');

            $order = Order::create([
                'queue_number' => 'A' . str_pad($nextQueueNumber++, 3, '0', STR_PAD_LEFT),
                'user_id' => $kasir->id,
                'source' => 'cashier',
                'table_id' => null,
                'order_type' => $orderType,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'payment_method' => 'cash',
                'payment_status' => 'settlement',
                'paid_at' => $createdAt,
                'status' => 'completed',
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $order->items()->createMany($items);
        }

        $this->command->info("Done. Total orders in DB now: " . Order::count());
    }
}