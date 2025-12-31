<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CustomerPriceList Model
 * 
 * Defines customer-specific pricing for B2B customers.
 * Can apply to specific product, entire brand, or entire category.
 */
class CustomerPriceList extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'custom_price',
        'valid_from',
        'valid_until',
        'notes',
    ];

    protected $casts = [
        'custom_price' => 'decimal:2',
        'valid_from' => 'date',
        'valid_until' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope for currently valid price lists.
     */
    public function scopeValid($query)
    {
        return $query
            ->where(fn($q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', now()))
            ->where(fn($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', now()));
    }

    /**
     * Scope for price lists applicable to a specific product.
     * Simplified to only match by product_id since brand_id/category_id columns don't exist.
     */
    public function scopeForProduct($query, Product $product)
    {
        return $query->where('product_id', $product->id);
    }

    /**
     * Calculate the price for a product using this price list entry.
     */
    public function calculatePrice(float $basePrice): float
    {
        if ($this->custom_price !== null) {
            return (float) $this->custom_price;
        }

        return $basePrice;
    }
}
