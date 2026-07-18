<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'price',
        'image',
        'is_available',
    ];

    // Auto-append imageUrl ke JSON response
    protected $appends = ['imageUrl'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_available' => 'boolean',
        ];
    }

    // Accessor: convert image path → full URL
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) return null;
        $base = env('IMAGE_BASE_URL') ?: config('app.url');
        return rtrim($base, '/') . '/img/' . $this->image;
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}