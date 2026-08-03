<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'queue_number',
        'user_id',
        'source',
        'table_id',
        'customer_name',
        'order_type',
        'subtotal',
        'tax',
        'total',
        'payment_method',
        'payment_status',
        'midtrans_order_id',
        'midtrans_transaction_id',
        'paid_at',
        'status',
        'voided_at',
        'voided_reason',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
            'voided_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Auto-hapus notif lonceng admin saat order ditandai selesai,
        // biar badge cuma nyisain order yang belum dihandle.
        // Match via queue_number yang ada di body notif ("Antrian #A092").
        static::updated(function (Order $order) {
            if ($order->wasChanged('status') && $order->status === 'completed') {
                \Illuminate\Support\Facades\DB::table('notifications')
                    ->where('data', 'like', '%Antrian #' . $order->queue_number . '%')
                    ->delete();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    
    public static function generateQueueNumber(): string
    {
        DB::table('counters')->where('name', 'order_queue')->increment('value');
        $nextNumber = DB::table('counters')->where('name', 'order_queue')->value('value');

        return 'A' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    // Samain counter dengan nomor order tertinggi yang ada. Dipanggil setelah
    // reset/seed, atau kalau DB di-restore dari dump yang gak bawa tabel counters.
    public static function syncQueueCounter(): int
    {
        $last = static::orderByDesc('id')->value('queue_number');
        $value = $last ? (int) substr($last, 1) : 0;

        DB::table('counters')->updateOrInsert(['name' => 'order_queue'], ['value' => $value]);

        return $value;
    }

    // Backstop kalau row lock kelewat (mis. tabel orders masih kosong, jadi gaada
    // row buat dikunci). Retry seluruh transaksi kalau queue_number bentrok.
    public static function withQueueNumberRetry(callable $callback, int $attempts = 3)
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                return $callback();
            } catch (QueryException $e) {
                $isDuplicateQueueNumber = $e->getCode() === '23000'
                    && str_contains($e->getMessage(), 'queue_number');

                if (! $isDuplicateQueueNumber || $attempt >= $attempts) {
                    throw $e;
                }
            }
        }
    }

    // Midtrans order ID unik per attempt (bisa retry payment)
    public static function generateMidtransOrderId(int $orderId): string
    {
        return 'MOOISTE-' . $orderId . '-' . time();
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeVoided($query)
    {
        return $query->where('status', 'voided');
    }

    public function scopeFromCashier($query)
    {
        return $query->where('source', 'cashier');
    }

    public function scopeFromCustomerQr($query)
    {
        return $query->where('source', 'customer_qr');
    }

    // Order dari QR meja yang udah bayar tapi belum di-konfirmasi kasir
    public function scopePendingConfirmation($query)
    {
        return $query->where('source', 'customer_qr')
                     ->where('status', 'paid');
    }

    // Order dari QR yang belum dibayar (buat cleanup expired)
    public function scopeAwaitingPayment($query)
    {
        return $query->where('status', 'pending_payment');
    }

    // Helper: status paid = udah bayar via midtrans
    public function isPaid(): bool
    {
        return $this->payment_status === 'settlement';
    }

    public function isFromQr(): bool
    {
        return $this->source === 'customer_qr';
    }
}