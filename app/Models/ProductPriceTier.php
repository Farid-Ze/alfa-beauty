<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ProductPriceTier Model
 * 
 * Defines volume-based tiered pricing for products.
 * Buy more, pay less per unit.
 */
class ProductPriceTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'min_quantity',
        'max_quantity',
        'unit_price',
        'discount_percent',
    ];

    protected $casts = [
        'min_quantity' => 'integer',
        'max_quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Check if this tier applies to a given quantity.
     * Note: Only checks min_quantity since max_quantity column doesn't exist.
     */
    public function appliesTo(int $quantity): bool
    {
        return $quantity >= $this->min_quantity;
    }

    /**
     * Calculate the unit price for this tier.
     */
    public function calculateUnitPrice(float $basePrice): float
    {
        if ($this->unit_price !== null) {
            return (float) $this->unit_price;
        }

        if ($this->discount_percent !== null) {
            return $basePrice * (1 - $this->discount_percent / 100);
        }

        return $basePrice;
    }

    /**
     * Scope to find the applicable tier for a quantity.
     * Note: Only uses min_quantity since max_quantity column doesn't exist in database.
     */
    public function scopeForQuantity($query, int $quantity)
    {
        return $query->where('min_quantity', '<=', $quantity);
    }
}
