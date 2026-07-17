<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // user_id jadi nullable karena order dari QR meja gada kasir yg input
            $table->foreignId('user_id')->nullable()->change();

            // Sumber & meja
            $table->enum('source', ['cashier', 'customer_qr'])->default('cashier')->after('user_id');
            $table->foreignId('table_id')->nullable()->after('source')
                  ->constrained('tables')->nullOnDelete();

            // Customer info (opsional, kalau customer isi nama pas self-order)
            $table->string('customer_name')->nullable()->after('table_id');

            // Payment tracking
            $table->enum('payment_status', ['unpaid', 'pending', 'settlement', 'expired', 'failed'])
                  ->default('settlement')->after('payment_method'); // default settlement biar order cashier lama gak keganggu
            $table->string('midtrans_order_id')->nullable()->unique()->after('payment_status');
            $table->string('midtrans_transaction_id')->nullable()->after('midtrans_order_id');
            $table->timestamp('paid_at')->nullable()->after('midtrans_transaction_id');

            $table->index(['source', 'status']);
            $table->index('payment_status');
        });

        // Enum status baru: tambah pending_payment & preparing untuk QR flow
        // MySQL enum change via raw
        \DB::statement("ALTER TABLE orders MODIFY status ENUM('pending_payment','paid','preparing','completed','voided','expired') DEFAULT 'completed'");

        // Payment method: tambah qris_midtrans
        \DB::statement("ALTER TABLE orders MODIFY payment_method ENUM('cash','qris_midtrans') DEFAULT 'cash'");
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['source', 'status']);
            $table->dropIndex(['payment_status']);
            $table->dropForeign(['table_id']);
            $table->dropColumn([
                'source', 'table_id', 'customer_name',
                'payment_status', 'midtrans_order_id',
                'midtrans_transaction_id', 'paid_at',
            ]);
            $table->foreignId('user_id')->nullable(false)->change();
        });

        \DB::statement("ALTER TABLE orders MODIFY status ENUM('completed','voided') DEFAULT 'completed'");
        \DB::statement("ALTER TABLE orders MODIFY payment_method ENUM('cash') DEFAULT 'cash'");
    }
};
