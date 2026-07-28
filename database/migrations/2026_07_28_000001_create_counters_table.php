<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Counter buat nomor antrian. Dipakai supaya generate queue_number cukup ngunci
// SATU baris lewat primary key (record lock murni). Cara lama — baca order terakhir
// pakai MAX/latest — bikin dua order bersamaan dapet nomor sama; kalau dikasih
// lockForUpdate malah kena gap lock dan deadlock pas INSERT.
return new class extends Migration {
    public function up(): void
    {
        Schema::create('counters', function (Blueprint $table) {
            $table->string('name', 50)->primary();
            $table->unsignedBigInteger('value')->default(0);
        });

        // Mulai dari nomor order terakhir biar antrian gak mundur di DB yang udah jalan.
        $last = DB::table('orders')->orderByDesc('id')->value('queue_number');

        DB::table('counters')->insert([
            'name' => 'order_queue',
            'value' => $last ? (int) substr($last, 1) : 0,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('counters');
    }
};