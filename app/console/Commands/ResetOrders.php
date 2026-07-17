<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class ResetOrders extends Command
{
    protected $signature = 'orders:reset {--seed : Re-seed 150 weighted dummy orders after reset}';

    protected $description = 'Hapus semua order & order_items. Optional: re-seed weighted dummy orders.';

    public function handle(): int
    {
        if (! $this->confirm('Yakin mau hapus SEMUA order & order_items?', false)) {
            $this->warn('Cancelled.');
            return self::SUCCESS;
        }

        DB::table('order_items')->delete();
        $deleted = DB::table('orders')->delete();

        $this->info("✓ Deleted {$deleted} orders + all order_items.");

        if ($this->option('seed')) {
            $this->info('Re-seeding weighted dummy orders...');
            Artisan::call('db:seed', ['--class' => 'OrderSeeder'], $this->output);
        }

        return self::SUCCESS;
    }
}