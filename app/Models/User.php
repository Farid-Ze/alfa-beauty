<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * User Model
 *
 * @property int $id
 * @property string $name
 * @property string|null $company_name
 * @property string $email
 * @property string|null $phone
 * @property string $password
 * @property int $points
 * @property float $total_spend
 * @property int|null $loyalty_tier_id
 * @property string|null $customer_type
 * @property string|null $business_name
 * @property string|null $npwp
 * @property \Carbon\Carbon|null $email_verified_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read LoyaltyTier|null $loyaltyTier
 * @property-read CustomerPaymentTerm|null $paymentTerm
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'company_name',
        'email',
        'phone',
        'password',
        'points',
        'total_spend',
        'loyalty_tier_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'points' => 'integer',
            'total_spend' => 'decimal:2',
        ];
    }

    public function loyaltyTier()
    {
        return $this->belongsTo(LoyaltyTier::class);
    }

    public function pointTransactions()
    {
        return $this->hasMany(PointTransaction::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get customer payment terms.
     */
    public function paymentTerm()
    {
        return $this->hasOne(CustomerPaymentTerm::class);
    }

    /**
     * Get customer-specific price lists.
     */
    public function priceLists()
    {
        return $this->hasMany(CustomerPriceList::class);
    }

    /**
     * Check if user has B2B pricing.
     */
    public function getHasB2bPricingAttribute(): bool
    {
        return $this->priceLists()->valid()->exists();
    }

    /**
     * Check if user can use credit terms.
     * @phpstan-ignore-next-line
     */
    public function getCanUseCreditAttribute(): bool
    {
        /** @phpstan-ignore-next-line */
        return $this->paymentTerm?->is_approved 
            && $this->paymentTerm?->term_type !== CustomerPaymentTerm::TERM_COD;
    }

    /**
     * Get available credit amount.
     */
    public function getAvailableCreditAttribute(): float
    {
        return $this->paymentTerm?->available_credit ?? 0;
    }

    // ======================================================
    // LOYALTY POINTS SYSTEM METHODS
    // ======================================================

    /**
     * Calculate points earned from a purchase amount.
     * Rule: 1 point per Rp 10,000
     * 
     * @param float $amount Purchase amount in IDR
     * @return int Points to earn
     */
    public function calculatePointsForPurchase(float $amount): int
    {
        $multiplier = $this->loyaltyTier?->point_multiplier ?? 1.0;
        $basePoints = floor($amount / 10000);
        return (int) floor($basePoints * $multiplier);
    }

    /**
     * Add points to user account.
     * Creates transaction record for audit trail.
     * 
     * @param int $points Points to add
     * @param string $type Transaction type (purchase, bonus, review, etc.)
     * @param int|null $orderId Related order ID
     * @param string|null $description Optional description
     * @return PointTransaction
     */
    public function addPoints(int $points, string $type, ?int $orderId = null, ?string $description = null): PointTransaction
    {
        $this->increment('points', $points);

        return $this->pointTransactions()->create([
            'type' => $type,
            'amount' => $points,
            'order_id' => $orderId,
            'description' => $description ?? "Earned {$points} points",
            'balance_after' => $this->fresh()->points,
        ]);
    }

    /**
     * Spend/redeem points from user account.
     * 
     * @param int $points Points to spend
     * @param string $type Transaction type (redemption, etc.)
     * @param int|null $orderId Related order ID
     * @return PointTransaction|false Returns false if insufficient points
     */
    public function spendPoints(int $points, string $type, ?int $orderId = null): PointTransaction|false
    {
        if ($this->points < $points) {
            return false;
        }

        $this->decrement('points', $points);

        return $this->pointTransactions()->create([
            'type' => $type,
            'amount' => -$points, // Negative for spend
            'order_id' => $orderId,
            'description' => "Spent {$points} points",
            'balance_after' => $this->fresh()->points,
        ]);
    }

    /**
     * Update user tier based on total_spend.
     * Called after order completion.
     * 
     * Tiers (from proposal):
     * - Bronze: Rp 0 (default)
     * - Silver: Rp 5,000,000+
     * - Gold: Rp 15,000,000+
     * 
     * @param float|null $additionalSpend Amount to add to total_spend first
     * @return LoyaltyTier|null The new tier (null if no change)
     */
    public function updateTier(?float $additionalSpend = null): ?LoyaltyTier
    {
        // Update total_spend if additional amount provided
        if ($additionalSpend !== null && $additionalSpend > 0) {
            $this->increment('total_spend', $additionalSpend);
            $this->refresh();
        }

        // Find the appropriate tier based on total_spend
        $newTier = LoyaltyTier::where('min_spend', '<=', $this->total_spend)
            ->orderByDesc('min_spend')
            ->first();

        if ($newTier && $newTier->id !== $this->loyalty_tier_id) {
            $oldTier = $this->loyaltyTier;
            $this->update(['loyalty_tier_id' => $newTier->id]);
            
            // Log tier upgrade for analytics
            /** @phpstan-ignore-next-line */
            \Illuminate\Support\Facades\Log::info('User tier upgraded', [
                'user_id' => $this->id,
                'old_tier' => $oldTier?->name,
                'new_tier' => $newTier->name,
                'total_spend' => $this->total_spend,
            ]);

            return $newTier;
        }

        return null;
    }

    /**
     * Get the discount percentage for current tier.
     * 
     * @return float Discount percent (0 to 100)
     */
    public function getTierDiscountAttribute(): float
    {
        return $this->loyaltyTier?->discount_percent ?? 0;
    }

    /**
     * Get display name for tier badge.
     */
    public function getTierBadgeAttribute(): string
    {
        return $this->loyaltyTier?->name ?? 'Guest';
    }

    /**
     * Get badge color for tier.
     */
    public function getTierColorAttribute(): string
    {
        return $this->loyaltyTier?->badge_color ?? '#808080';
    }
}

