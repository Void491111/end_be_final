<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    // Generate queue number: A001, A002, ... A999, A1000, ...
    public static function generateQueueNumber(): string
    {
        $lastOrder = static::latest('id')->first();
        $nextNumber = $lastOrder ? ((int) substr($lastOrder->queue_number, 1)) + 1 : 1;
        return 'A' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
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
