<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * OrderDiscount Model
 * 
 * Tracks which discounts were applied to each order.
 * Provides audit trail for discount calculations.
 */
class OrderDiscount extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'discount_rule_id',
        'order_item_id',
        'discount_type',
        'discount_code',
        'discount_name',
        'original_amount',
        'discount_amount',
        'final_amount',
        'calculation_details',
    ];

    protected $casts = [
        'original_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'calculation_details' => 'array',
    ];

    /* ─────────────────────────────────────────────────────────────
     * Relationships
     * ───────────────────────────────────────────────────────────── */

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function discountRule(): BelongsTo
    {
        return $this->belongsTo(DiscountRule::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /* ─────────────────────────────────────────────────────────────
     * Accessors
     * ───────────────────────────────────────────────────────────── */

    public function getDiscountPercentAttribute(): float
    {
        if ($this->original_amount == 0) {
            return 0;
        }
        
        return round(($this->discount_amount / $this->original_amount) * 100, 2);
    }

    public function getIsOrderLevelAttribute(): bool
    {
        return $this->order_item_id === null;
    }

    /* ─────────────────────────────────────────────────────────────
     * Static Factory Methods
     * ───────────────────────────────────────────────────────────── */

    public static function createFromRule(
        Order $order,
        DiscountRule $rule,
        float $originalAmount,
        float $discountAmount,
        ?OrderItem $orderItem = null,
        ?array $calculationDetails = null
    ): self {
        return self::create([
            'order_id' => $order->id,
            'discount_rule_id' => $rule->id,
            'order_item_id' => $orderItem?->id,
            'discount_type' => $rule->discount_type,
            'discount_code' => $rule->code,
            'discount_name' => $rule->name,
            'original_amount' => $originalAmount,
            'discount_amount' => $discountAmount,
            'final_amount' => $originalAmount - $discountAmount,
            'calculation_details' => $calculationDetails,
        ]);
    }

    public static function createManual(
        Order $order,
        string $name,
        string $type,
        float $originalAmount,
        float $discountAmount,
        ?string $code = null,
        ?OrderItem $orderItem = null
    ): self {
        return self::create([
            'order_id' => $order->id,
            'order_item_id' => $orderItem?->id,
            'discount_type' => $type,
            'discount_code' => $code,
            'discount_name' => $name,
            'original_amount' => $originalAmount,
            'discount_amount' => $discountAmount,
            'final_amount' => $originalAmount - $discountAmount,
        ]);
    }
}
