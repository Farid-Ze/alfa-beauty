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
 * 
 * PRIORITY ORDER:
 * 1. Product-specific pricing (highest)
 * 2. Brand-level pricing
 * 3. Category-level pricing
 * 4. Global customer discount (lowest)
 */
class CustomerPriceList extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'brand_id',
        'category_id',
        'custom_price',
        'discount_percent',
        'min_quantity',
        'valid_from',
        'valid_until',
        'priority',
        'notes',
    ];

    protected $casts = [
        'custom_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'min_quantity' => 'integer',
        'priority' => 'integer',
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

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
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
     * Uses simple product_id matching (brand/category hierarchy handled in service).
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

        if ($this->discount_percent !== null) {
            return $basePrice * (1 - $this->discount_percent / 100);
        }

        return $basePrice;
    }
}


