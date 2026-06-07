<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('queue_number')->unique(); // A001, A002, ...
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // kasir
            $table->enum('order_type', ['dine_in', 'takeaway']);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->enum('payment_method', ['cash'])->default('cash');
            $table->enum('status', ['completed', 'voided'])->default('completed');
            $table->timestamp('voided_at')->nullable();
            $table->text('voided_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};