<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'is_active',
        'qr_generated_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'qr_generated_at' => 'datetime',
        ];
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // URL yang di-encode di QR
    public function getOrderUrlAttribute(): string
    {
        $baseUrl = config('app.frontend_url', 'http://localhost:3000');
        return rtrim($baseUrl, '/') . '/order/' . $this->code;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
