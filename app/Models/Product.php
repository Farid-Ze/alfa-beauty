<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'images' => 'array',
        'is_halal' => 'boolean',
        'is_vegan' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    /**
     * Alias for base_price to ensure consistent access.
     */
    public function getPriceAttribute(): float
    {
        return (float) $this->base_price;
    }

    /**
     * Points earned per purchase (1 point per Rp 10,000).
     */
    public function getPointsAttribute(): int
    {
        return (int) floor($this->base_price / 10000);
    }

    /**
     * Check if product is in stock.
     */
    public function getInStockAttribute(): bool
    {
        return $this->stock > 0;
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
