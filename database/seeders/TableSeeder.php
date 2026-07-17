<?php

namespace Database\Seeders;

use App\Models\Table;
use Illuminate\Database\Seeder;

class TableSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $code = 'T' . str_pad($i, 2, '0', STR_PAD_LEFT);

            Table::updateOrCreate(
                ['code' => $code],
                [
                    'name' => 'Meja ' . $i,
                    'is_active' => true,
                ]
            );
        }
    }
}
