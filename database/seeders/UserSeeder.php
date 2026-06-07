<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin Mooiste',
            'email' => 'admin@mooiste.com',
            'password' => 'password',
            'role' => 'admin',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Person 1',
            'email' => 'kasir@mooiste.com',
            'password' => 'password',
            'role' => 'cashier',
            'is_active' => true,
        ]);
    }
}