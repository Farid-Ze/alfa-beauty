<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CustomerPaymentTerm Model
 * 
 * Defines B2B payment terms for customers.
 * Supports Net 30, Net 60, credit limits, and early payment discounts.
 *
 * @property int $id
 * @property int $user_id
 * @property string $term_type
 * @property float|null $credit_limit
 * @property float|null $credit_used
 * @property float|null $early_payment_discount
 * @property bool $is_approved
 * @property \Carbon\Carbon|null $approved_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read float $available_credit
 * @property-read User $user
 */
class CustomerPaymentTerm extends Model
{
    use HasFactory;

    const TERM_COD = 'cod';
    const TERM_NET_15 = 'net_15';
    const TERM_NET_30 = 'net_30';
    const TERM_NET_60 = 'net_60';
    const TERM_NET_90 = 'net_90';

    protected $fillable = [
        'user_id',
        'term_type',
        'credit_limit',
        'current_balance',
        'early_payment_discount_percent',
        'early_payment_days',
        'is_approved',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'credit_limit' => 'float',
        'current_balance' => 'float',
        'early_payment_discount_percent' => 'float',
        'early_payment_days' => 'integer',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get available credit (credit limit minus outstanding balance).
     */
    public function getAvailableCreditAttribute(): float
    {
        return max(0, $this->credit_limit - $this->current_balance);
    }

    /**
     * Check if customer can use credit terms for a given amount.
     */
    public function canUseCredit(float $amount): bool
    {
        if (!$this->is_approved) {
            return false;
        }

        if ($this->term_type === self::TERM_COD) {
            return false;
        }

        return $this->available_credit >= $amount;
    }

    /**
     * Get payment due days from term type.
     */
    public function getPaymentDueDaysAttribute(): int
    {
        return match($this->term_type) {
            self::TERM_NET_15 => 15,
            self::TERM_NET_30 => 30,
            self::TERM_NET_60 => 60,
            self::TERM_NET_90 => 90,
            default => 0,
        };
    }

    /**
     * Get human-readable term label.
     */
    public function getTermLabelAttribute(): string
    {
        return match($this->term_type) {
            self::TERM_COD => 'Cash on Delivery',
            self::TERM_NET_15 => 'Net 15 Days',
            self::TERM_NET_30 => 'Net 30 Days',
            self::TERM_NET_60 => 'Net 60 Days',
            self::TERM_NET_90 => 'Net 90 Days',
            default => 'Unknown',
        };
    }

    /**
     * Increase balance when new order is placed on credit.
     */
    public function addToBalance(float $amount): void
    {
        $this->increment('current_balance', $amount);
    }

    /**
     * Decrease balance when payment is received.
     */
    public function reduceBalance(float $amount): void
    {
        $this->decrement('current_balance', min($amount, $this->current_balance));
    }
}
