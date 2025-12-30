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
     * Note: price_at_add stores B2B price, so we compare with it.
     * True price comparison requires PricingService context (user, quantity).
     */
    public function getPriceChangedAttribute(): bool
    {
        // This is a simplified check - true B2B price change detection
        // happens in CartService::refreshPrices()
        if (!$this->price_at_add) {
            return false;
        }

        // Compare with stored price - changes detected by refreshPrices()
        return false; // Let CartService handle price change detection
    }

    /**
     * Get the stored price at time of add (includes B2B pricing).
     */
    public function getStoredPriceAttribute(): float
    {
        return (float) ($this->price_at_add ?? $this->product->base_price);
    }

    /**
     * Get line total using stored B2B price.
     */
    public function getLineTotalAttribute(): float
    {
        return $this->stored_price * $this->quantity;
    }
}
