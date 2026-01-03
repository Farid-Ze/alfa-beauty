<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * DiscountRule Model
 * 
 * Flexible discount system supporting:
 * - Percentage discounts
 * - Fixed amount discounts
 * - Buy X Get Y promotions
 * - Bundle pricing
 * - Free items
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property string $discount_type
 * @property float $discount_value
 * @property int|null $buy_quantity
 * @property int|null $get_quantity
 * @property float|null $get_discount_percent
 * @property float|null $min_order_amount
 * @property int|null $min_quantity
 * @property float|null $max_discount_amount
 * @property int|null $usage_limit
 * @property int $usage_count
 * @property int|null $per_user_limit
 * @property int|null $product_id
 * @property int|null $brand_id
 * @property int|null $category_id
 * @property array|null $loyalty_tier_ids
 * @property array|null $user_ids
 * @property Carbon|null $valid_from
 * @property Carbon|null $valid_until
 * @property bool $is_active
 * @property bool $is_stackable
 * @property int $priority
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Product|null $product
 * @property-read Brand|null $brand
 * @property-read Category|null $category
 */
class DiscountRule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'buy_quantity',
        'get_quantity',
        'get_discount_percent',
        'min_order_amount',
        'min_quantity',
        'max_discount_amount',
        'usage_limit',
        'usage_count',
        'per_user_limit',
        'product_id',
        'brand_id',
        'category_id',
        'loyalty_tier_ids',
        'user_ids',
        'valid_from',
        'valid_until',
        'is_active',
        'is_stackable',
        'priority',
    ];

    protected $casts = [
        'discount_value' => 'float',
        'get_discount_percent' => 'float',
        'min_order_amount' => 'float',
        'max_discount_amount' => 'float',
        'buy_quantity' => 'integer',
        'get_quantity' => 'integer',
        'min_quantity' => 'integer',
        'usage_limit' => 'integer',
        'usage_count' => 'integer',
        'per_user_limit' => 'integer',
        'priority' => 'integer',
        'loyalty_tier_ids' => 'array',
        'user_ids' => 'array',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'is_active' => 'boolean',
        'is_stackable' => 'boolean',
    ];

    /* ─────────────────────────────────────────────────────────────
     * Constants
     * ───────────────────────────────────────────────────────────── */

    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FIXED_AMOUNT = 'fixed_amount';
    public const TYPE_BUY_X_GET_Y = 'buy_x_get_y';
    public const TYPE_FREE_ITEM = 'free_item';
    public const TYPE_BUNDLE_PRICE = 'bundle_price';

    public const TYPES = [
        self::TYPE_PERCENTAGE => 'Persentase',
        self::TYPE_FIXED_AMOUNT => 'Potongan nominal',
        self::TYPE_BUY_X_GET_Y => 'Beli X Gratis Y',
        self::TYPE_FREE_ITEM => 'Gratis produk',
        self::TYPE_BUNDLE_PRICE => 'Harga bundle',
    ];

    /* ─────────────────────────────────────────────────────────────
     * Relationships
     * ───────────────────────────────────────────────────────────── */

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

    public function orderDiscounts(): HasMany
    {
        return $this->hasMany(OrderDiscount::class);
    }

    /* ─────────────────────────────────────────────────────────────
     * Scopes
     * ───────────────────────────────────────────────────────────── */

    public function scopeActive($query)
    {
        return $query->whereRaw('is_active = true');
    }

    public function scopeValid($query)
    {
        $now = now();
        return $query->where(function ($q) use ($now) {
            $q->whereNull('valid_from')->orWhere('valid_from', '<=', $now);
        })->where(function ($q) use ($now) {
            $q->whereNull('valid_until')->orWhere('valid_until', '>=', $now);
        });
    }

    public function scopeAvailable($query)
    {
        return $query->active()->valid()->where(function ($q) {
            $q->whereNull('usage_limit')
              ->orWhereColumn('usage_count', '<', 'usage_limit');
        });
    }

    public function scopeForProduct($query, int $productId)
    {
        return $query->where(function ($q) use ($productId) {
            $q->where('product_id', $productId)->orWhereNull('product_id');
        });
    }

    public function scopeByPriority($query)
    {
        return $query->orderByDesc('priority');
    }

    /* ─────────────────────────────────────────────────────────────
     * Accessors
     * ───────────────────────────────────────────────────────────── */

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->discount_type] ?? $this->discount_type;
    }

    public function getIsValidAttribute(): bool
    {
        $now = now();
        
        if ($this->valid_from && $this->valid_from > $now) {
            return false;
        }
        
        if ($this->valid_until && $this->valid_until < $now) {
            return false;
        }
        
        return true;
    }

    public function getHasUsagesLeftAttribute(): bool
    {
        if ($this->usage_limit === null) {
            return true;
        }
        
        return $this->usage_count < $this->usage_limit;
    }

    /* ─────────────────────────────────────────────────────────────
     * Business Logic
     * ───────────────────────────────────────────────────────────── */

    /**
     * Check if discount applies to given context
     * @phpstan-ignore-next-line
     */
    public function appliesTo(
        ?Product $product = null,
        ?User $user = null,
        float $orderAmount = 0,
        int $quantity = 0
    ): bool {
        // Check product/brand/category targeting
        /** @phpstan-ignore-next-line */
        if ($this->product_id && (!$product || $product->id !== $this->product_id)) {
            return false;
        }
        
        /** @phpstan-ignore-next-line */
        if ($this->brand_id && (!$product || $product->brand_id !== $this->brand_id)) {
            return false;
        }
        
        /** @phpstan-ignore-next-line */
        if ($this->category_id && (!$product || $product->category_id !== $this->category_id)) {
            return false;
        }
        
        // Check user targeting
        /** @phpstan-ignore-next-line */
        if ($this->user_ids && (!$user || !in_array($user->id, $this->user_ids))) {
            return false;
        }
        
        // Check loyalty tier targeting
        if ($this->loyalty_tier_ids && (!$user || !in_array($user->loyalty_tier_id, $this->loyalty_tier_ids))) {
            return false;
        }
        
        // Check minimums
        if ($this->min_order_amount && $orderAmount < $this->min_order_amount) {
            return false;
        }
        
        if ($this->min_quantity && $quantity < $this->min_quantity) {
            return false;
        }
        
        return true;
    }

    /**
     * Calculate discount amount for given values
     */
    public function calculateDiscount(float $amount, int $quantity = 1): float
    {
        $discount = 0;
        
        switch ($this->discount_type) {
            case 'percentage':
                $discount = $amount * ($this->discount_value / 100);
                break;
                
            case 'fixed_amount':
                $discount = $this->discount_value;
                break;
                
            case 'buy_x_get_y':
                if ($quantity >= $this->buy_quantity) {
                    $sets = floor($quantity / ($this->buy_quantity + $this->get_quantity));
                    $freeItems = $sets * $this->get_quantity;
                    $unitPrice = $amount / $quantity;
                    $discount = $freeItems * $unitPrice * ($this->get_discount_percent / 100);
                }
                break;
        }
        
        // Apply cap if set
        if ($this->max_discount_amount && $discount > $this->max_discount_amount) {
            $discount = $this->max_discount_amount;
        }
        
        return round($discount, 2);
    }

    /**
     * Increment usage counter
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Check if user can use this discount
     */
    public function canBeUsedBy(User $user): bool
    {
        if (!$this->is_active || !$this->is_valid || !$this->has_usages_left) {
            return false;
        }
        
        if ($this->per_user_limit) {
            $userUsages = $this->orderDiscounts()
                ->whereHas('order', fn($q) => $q->where('user_id', $user->id))
                ->count();
                
            if ($userUsages >= $this->per_user_limit) {
                return false;
            }
        }
        
        return $this->appliesTo(user: $user);
    }
}
