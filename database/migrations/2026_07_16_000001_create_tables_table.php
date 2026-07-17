<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // T01, T02, T03
            $table->string('name'); // "Meja 1", "Meja VIP"
            $table->boolean('is_active')->default(true);
            $table->timestamp('qr_generated_at')->nullable();
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tables');
    }
};
