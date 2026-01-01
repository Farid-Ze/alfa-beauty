<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CustomerOrderSetting Model
 * 
 * Per-customer order configuration including:
 * - Minimum order amounts/quantities
 * - Payment terms and credit limits
 * - Shipping preferences
 */
class CustomerOrderSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'min_order_amount',
        'min_order_units',
        'default_payment_term_days',
        'credit_limit',
        'current_credit_used',
        'free_shipping_eligible',
        'free_shipping_threshold',
        'require_po_number',
        'allow_backorder',
        'allow_partial_delivery',
    ];

    protected $casts = [
        'min_order_amount' => 'decimal:2',
        'min_order_units' => 'integer',
        'default_payment_term_days' => 'integer',
        'credit_limit' => 'decimal:2',
        'current_credit_used' => 'decimal:2',
        'free_shipping_threshold' => 'decimal:2',
        'free_shipping_eligible' => 'boolean',
        'require_po_number' => 'boolean',
        'allow_backorder' => 'boolean',
        'allow_partial_delivery' => 'boolean',
    ];

    /* ─────────────────────────────────────────────────────────────
     * Relationships
     * ───────────────────────────────────────────────────────────── */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /* ─────────────────────────────────────────────────────────────
     * Business Logic
     * ───────────────────────────────────────────────────────────── */

    /**
     * Check if order meets minimum requirements
     */
    public function validateOrder(float $orderAmount, int $totalUnits): array
    {
        $errors = [];
        
        if ($this->min_order_amount && $orderAmount < $this->min_order_amount) {
            $errors['min_order_amount'] = sprintf(
                'Minimum order Rp %s. Saat ini: Rp %s',
                number_format((float) $this->min_order_amount, 0, ',', '.'),
                number_format($orderAmount, 0, ',', '.')
            );
        }
        
        if ($this->min_order_units && $totalUnits < $this->min_order_units) {
            $errors['min_order_units'] = sprintf(
                'Minimum order %d unit. Saat ini: %d unit',
                $this->min_order_units,
                $totalUnits
            );
        }
        
        return $errors;
    }

    /**
     * Check if order is within credit limit
     */
    public function hasAvailableCredit(float $orderAmount): bool
    {
        if ($this->credit_limit === null) {
            return true;
        }
        
        $availableCredit = $this->credit_limit - $this->current_credit_used;
        return $orderAmount <= $availableCredit;
    }

    /**
     * Get available credit amount
     */
    public function getAvailableCreditAttribute(): ?float
    {
        if ($this->credit_limit === null) {
            return null;
        }
        
        return max(0, $this->credit_limit - $this->current_credit_used);
    }

    /**
     * Check if order qualifies for free shipping
     */
    public function qualifiesForFreeShipping(float $orderAmount): bool
    {
        if (!$this->free_shipping_eligible) {
            return false;
        }
        
        if ($this->free_shipping_threshold === null) {
            return true;
        }
        
        return $orderAmount >= $this->free_shipping_threshold;
    }

    /**
     * Reserve credit for an order
     */
    public function reserveCredit(float $amount): bool
    {
        if (!$this->hasAvailableCredit($amount)) {
            return false;
        }
        
        $this->increment('current_credit_used', $amount);
        return true;
    }

    /**
     * Release credit when order is paid/cancelled
     */
    public function releaseCredit(float $amount): void
    {
        $this->decrement('current_credit_used', min($amount, $this->current_credit_used));
    }

    /* ─────────────────────────────────────────────────────────────
     * Static Factory Methods
     * ───────────────────────────────────────────────────────────── */

    public static function getOrCreateForUser(User $user): self
    {
        return self::firstOrCreate(
            ['user_id' => $user->id],
            [
                'min_order_amount' => null,
                'min_order_units' => null,
                'default_payment_term_days' => 0,
                'free_shipping_eligible' => false,
            ]
        );
    }

    /**
     * Create default settings based on customer type
     */
    public static function createDefaultsForType(User $user, string $customerType): self
    {
        $defaults = match ($customerType) {
            'distributor' => [
                'min_order_amount' => 5000000, // 5 juta
                'default_payment_term_days' => 30,
                'credit_limit' => 50000000,
                'free_shipping_eligible' => true,
                'allow_partial_delivery' => true,
            ],
            'salon' => [
                'min_order_amount' => 500000, // 500rb
                'default_payment_term_days' => 14,
                'free_shipping_eligible' => true,
                'free_shipping_threshold' => 1000000,
            ],
            'reseller' => [
                'min_order_amount' => 1000000, // 1 juta
                'default_payment_term_days' => 7,
            ],
            default => [],
        };
        
        return self::create([
            'user_id' => $user->id,
            ...$defaults,
        ]);
    }
}
