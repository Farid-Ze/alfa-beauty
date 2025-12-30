<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    /** @use HasFactory<\Database\Factories\CartItemFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $with = ['product'];

    protected $casts = [
        'quantity' => 'integer',
        'price_at_add' => 'decimal:2',
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Check if price has changed since item was added.
     */
    public function getPriceChangedAttribute(): bool
    {
        if (!$this->price_at_add) {
            return false;
        }

        return $this->price_at_add != $this->product->base_price;
    }

    /**
     * Get the current effective price (latest from product).
     */
    public function getCurrentPriceAttribute(): float
    {
        return $this->product->base_price;
    }

    /**
     * Get price difference (positive = price increased).
     */
    public function getPriceDifferenceAttribute(): float
    {
        if (!$this->price_at_add) {
            return 0;
        }

        return $this->product->base_price - $this->price_at_add;
    }
}
