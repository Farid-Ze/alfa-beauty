<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

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
     */
    public function appliesTo(int $quantity): bool
    {
        if ($quantity < $this->min_quantity) {
            return false;
        }

        if ($this->max_quantity === null) {
            return true;
        }

        return $quantity <= $this->max_quantity;
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
     */
    public function scopeForQuantity($query, int $quantity)
    {
        return $query
            ->where('min_quantity', '<=', $quantity)
            ->where(function ($q) use ($quantity) {
                $q->whereNull('max_quantity')
                    ->orWhere('max_quantity', '>=', $quantity);
            });
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $hasUnit = $model->unit_price !== null;
            $hasDiscount = $model->discount_percent !== null;

            if ($hasUnit === $hasDiscount) {
                throw ValidationException::withMessages([
                    'unit_price' => 'Set either unit price or discount percent (not both).',
                    'discount_percent' => 'Set either unit price or discount percent (not both).',
                ]);
            }

            if ($model->min_quantity < 1) {
                throw ValidationException::withMessages([
                    'min_quantity' => 'Minimum quantity must be at least 1.',
                ]);
            }

            if ($model->max_quantity !== null && $model->max_quantity < $model->min_quantity) {
                throw ValidationException::withMessages([
                    'max_quantity' => 'Maximum quantity must be greater than or equal to minimum quantity.',
                ]);
            }

            if (empty($model->product_id)) {
                return;
            }

            $newMin = (int) $model->min_quantity;
            $newMax = $model->max_quantity === null ? null : (int) $model->max_quantity;

            $overlaps = self::query()
                ->where('product_id', $model->product_id)
                ->when($model->exists, fn ($q) => $q->whereKeyNot($model->getKey()))
                ->when($newMax !== null, fn ($q) => $q->where('min_quantity', '<=', $newMax))
                ->where(function ($q) use ($newMin) {
                    $q->whereNull('max_quantity')
                        ->orWhere('max_quantity', '>=', $newMin);
                })
                ->exists();

            if ($overlaps) {
                throw ValidationException::withMessages([
                    'min_quantity' => 'This tier overlaps an existing tier for the same product.',
                    'max_quantity' => 'This tier overlaps an existing tier for the same product.',
                ]);
            }
        });
    }
}
