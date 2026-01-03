<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

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
     * Matches by product_id, brand_id, category_id, or global (all null).
     */
    public function scopeForProduct($query, Product $product)
    {
        return $query->where(function ($q) use ($product) {
            $q->where('product_id', $product->id)
              ->orWhere('brand_id', $product->brand_id)
              ->orWhere('category_id', $product->category_id)
              ->orWhere(function ($q2) {
                  // Global discount (no specific product/brand/category)
                  $q2->whereNull('product_id')
                     ->whereNull('brand_id')
                     ->whereNull('category_id');
              });
        });
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

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $hasCustom = $model->custom_price !== null;
            $hasDiscount = $model->discount_percent !== null;

            if ($hasCustom === $hasDiscount) {
                throw ValidationException::withMessages([
                    'custom_price' => 'Set either custom price or discount percent (not both).',
                    'discount_percent' => 'Set either custom price or discount percent (not both).',
                ]);
            }

            $minQty = (int) ($model->min_quantity ?? 1);
            if ($minQty < 1) {
                throw ValidationException::withMessages([
                    'min_quantity' => 'Minimum quantity must be at least 1.',
                ]);
            }

            if ($model->valid_from && $model->valid_until && $model->valid_from > $model->valid_until) {
                throw ValidationException::withMessages([
                    'valid_from' => 'Valid from must be on or before valid until.',
                    'valid_until' => 'Valid until must be on or after valid from.',
                ]);
            }
        });
    }
}


