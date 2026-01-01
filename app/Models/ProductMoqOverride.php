<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ProductMoqOverride Model
 * 
 * Allows overriding product MOQ for specific customers, tiers, or customer types.
 * Enables differentiated ordering rules per business segment.
 */
class ProductMoqOverride extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'loyalty_tier_id',
        'customer_type',
        'min_order_qty',
        'order_increment',
        'max_order_qty',
    ];

    protected $casts = [
        'min_order_qty' => 'integer',
        'order_increment' => 'integer',
        'max_order_qty' => 'integer',
    ];

    /* ─────────────────────────────────────────────────────────────
     * Relationships
     * ───────────────────────────────────────────────────────────── */

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function loyaltyTier(): BelongsTo
    {
        return $this->belongsTo(LoyaltyTier::class);
    }

    /* ─────────────────────────────────────────────────────────────
     * Static Query Methods
     * ───────────────────────────────────────────────────────────── */

    /**
     * Get applicable MOQ for a user and product
     * Priority: User-specific > Tier-specific > Customer type > Product default
     */
    public static function getForUserAndProduct(User $user, Product $product): ?self
    {
        // First try user-specific override
        $override = self::where('product_id', $product->id)
            ->where('user_id', $user->id)
            ->first();
            
        if ($override) {
            return $override;
        }
        
        // Then try loyalty tier override
        if ($user->loyalty_tier_id) {
            $override = self::where('product_id', $product->id)
                ->where('loyalty_tier_id', $user->loyalty_tier_id)
                ->whereNull('user_id')
                ->first();
                
            if ($override) {
                return $override;
            }
        }
        
        // Then try customer type override
        if ($user->customer_type) {
            $override = self::where('product_id', $product->id)
                ->where('customer_type', $user->customer_type)
                ->whereNull('user_id')
                ->whereNull('loyalty_tier_id')
                ->first();
                
            if ($override) {
                return $override;
            }
        }
        
        return null;
    }

    /**
     * Get effective MOQ for a user and product (including product defaults)
     */
    public static function getEffectiveMoq(User $user, Product $product): array
    {
        $override = self::getForUserAndProduct($user, $product);
        
        if ($override) {
            return [
                'min_order_qty' => $override->min_order_qty,
                'order_increment' => $override->order_increment ?? $product->order_increment ?? 1,
                'max_order_qty' => $override->max_order_qty,
                'source' => 'override',
            ];
        }
        
        return [
            'min_order_qty' => $product->min_order_qty ?? 1,
            'order_increment' => $product->order_increment ?? 1,
            'max_order_qty' => null,
            'source' => 'product',
        ];
    }

    /* ─────────────────────────────────────────────────────────────
     * Validation
     * ───────────────────────────────────────────────────────────── */

    /**
     * Validate quantity against MOQ rules
     */
    public function validateQuantity(int $quantity): array
    {
        $errors = [];
        
        if ($quantity < $this->min_order_qty) {
            $errors['min_qty'] = sprintf(
                'Minimum order %d unit untuk produk ini',
                $this->min_order_qty
            );
        }
        
        if ($this->order_increment && $quantity % $this->order_increment !== 0) {
            $errors['increment'] = sprintf(
                'Quantity harus kelipatan %d',
                $this->order_increment
            );
        }
        
        if ($this->max_order_qty && $quantity > $this->max_order_qty) {
            $errors['max_qty'] = sprintf(
                'Maximum order %d unit untuk produk ini',
                $this->max_order_qty
            );
        }
        
        return $errors;
    }
}
