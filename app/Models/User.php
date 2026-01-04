<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

/**
 * User Model
 *
 * @property int $id
 * @property string $name
 * @property string|null $company_name
 * @property string $email
 * @property string|null $phone
 * @property string $password
 * @property string|null $role
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
 * @property-read \Illuminate\Database\Eloquent\Collection|Review[] $reviews
 * @property-read Cart|null $cart
 */
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @var array<string, bool> */
    protected static array $pointTransactionsColumnCache = [];

    protected static function pointTransactionsHasColumn(string $column): bool
    {
        if (!array_key_exists($column, self::$pointTransactionsColumnCache)) {
            self::$pointTransactionsColumnCache[$column] = Schema::hasColumn('point_transactions', $column);
        }

        return self::$pointTransactionsColumnCache[$column];
    }

    protected static function isUniqueConstraintViolation(\Illuminate\Database\QueryException $e): bool
    {
        $sqlState = $e->errorInfo[0] ?? null;
        $driverCode = $e->errorInfo[1] ?? null;

        // MySQL: SQLSTATE 23000 / driver 1062, Postgres: SQLSTATE 23505
        return $sqlState === '23000' || $sqlState === '23505' || $driverCode === 1062;
    }

    protected function auditEvent(array $payload): void
    {
        try {
            if (!Schema::hasTable('audit_events')) {
                return;
            }

            \App\Models\AuditEvent::create($payload);
        } catch (\Throwable $e) {
            Log::warning('AuditEvent write failed', [
                'error' => $e->getMessage(),
                'action' => $payload['action'] ?? null,
                'entity_type' => $payload['entity_type'] ?? null,
                'entity_id' => $payload['entity_id'] ?? null,
            ]);
        }
    }

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
     * Get user's cart.
     */
    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

    /**
     * Get user's reviews.
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
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
    public function addPoints(
        int $points,
        string $type,
        ?int $orderId = null,
        ?string $description = null,
        ?string $idempotencyKey = null,
        ?string $requestId = null,
    ): PointTransaction
    {
        return DB::transaction(function () use ($points, $type, $orderId, $description, $idempotencyKey, $requestId) {
            /** @var self $lockedUser */
            $lockedUser = self::whereKey($this->id)->lockForUpdate()->firstOrFail();

            $payload = [
                'user_id' => $lockedUser->id,
                'type' => $type,
                'points' => $points,
                'order_id' => $orderId,
                'description' => $description ?? "Earned {$points} points",
            ];

            if (self::pointTransactionsHasColumn('request_id')) {
                $payload['request_id'] = $requestId ?? request()?->attributes?->get('request_id');
            }

            $useIdempotency = $idempotencyKey && self::pointTransactionsHasColumn('idempotency_key');
            if ($useIdempotency) {
                $payload['idempotency_key'] = $idempotencyKey;
            }

            $created = false;
            if ($useIdempotency) {
                try {
                    $tx = PointTransaction::firstOrCreate(
                        ['idempotency_key' => $idempotencyKey],
                        $payload,
                    );
                    $created = $tx->wasRecentlyCreated;
                } catch (\Illuminate\Database\QueryException $e) {
                    if (!self::isUniqueConstraintViolation($e)) {
                        throw $e;
                    }
                    $tx = PointTransaction::where('idempotency_key', $idempotencyKey)->firstOrFail();
                }
            } else {
                $tx = $lockedUser->pointTransactions()->create($payload);
                $created = true;
            }

            if ($created) {
                $lockedUser->increment('points', $points);

                if (self::pointTransactionsHasColumn('balance_after')) {
                    $tx->forceFill(['balance_after' => $lockedUser->fresh()->points])->save();
                }
            }

            return $tx;
        });
    }

    /**
     * Spend/redeem points from user account.
     * 
     * @param int $points Points to spend
     * @param string $type Transaction type (redemption, etc.)
     * @param int|null $orderId Related order ID
     * @return PointTransaction|false Returns false if insufficient points
     */
    public function spendPoints(
        int $points,
        string $type,
        ?int $orderId = null,
        ?string $idempotencyKey = null,
        ?string $requestId = null,
    ): PointTransaction|false
    {
        return DB::transaction(function () use ($points, $type, $orderId, $idempotencyKey, $requestId) {
            /** @var self $lockedUser */
            $lockedUser = self::whereKey($this->id)->lockForUpdate()->firstOrFail();

            $useIdempotency = $idempotencyKey && self::pointTransactionsHasColumn('idempotency_key');
            if ($useIdempotency) {
                $existing = PointTransaction::where('idempotency_key', $idempotencyKey)->first();
                if ($existing) {
                    return $existing;
                }
            }

            if ($lockedUser->points < $points) {
                return false;
            }

            $payload = [
                'user_id' => $lockedUser->id,
                'type' => $type,
                'points' => -$points, // Negative for spend
                'order_id' => $orderId,
                'description' => "Spent {$points} points",
            ];

            if (self::pointTransactionsHasColumn('request_id')) {
                $payload['request_id'] = $requestId ?? request()?->attributes?->get('request_id');
            }

            $created = false;
            if ($useIdempotency) {
                $payload['idempotency_key'] = $idempotencyKey;
                try {
                    $tx = PointTransaction::firstOrCreate(
                        ['idempotency_key' => $idempotencyKey],
                        $payload,
                    );
                    $created = $tx->wasRecentlyCreated;
                } catch (\Illuminate\Database\QueryException $e) {
                    if (!self::isUniqueConstraintViolation($e)) {
                        throw $e;
                    }
                    $tx = PointTransaction::where('idempotency_key', $idempotencyKey)->firstOrFail();
                }
            } else {
                $tx = $lockedUser->pointTransactions()->create($payload);
                $created = true;
            }

            if ($created) {
                $lockedUser->decrement('points', $points);

                if (self::pointTransactionsHasColumn('balance_after')) {
                    $tx->forceFill(['balance_after' => $lockedUser->fresh()->points])->save();
                }
            }

            return $tx;
        });
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
        return DB::transaction(function () use ($additionalSpend) {
            /** @var self $lockedUser */
            $lockedUser = self::whereKey($this->id)->lockForUpdate()->firstOrFail();

            // Update total_spend if additional amount provided
            if ($additionalSpend !== null && $additionalSpend > 0) {
                $lockedUser->increment('total_spend', $additionalSpend);
                $lockedUser->refresh();
            }

            // Find the appropriate tier based on total_spend
            $newTier = LoyaltyTier::where('min_spend', '<=', $lockedUser->total_spend)
                ->orderByDesc('min_spend')
                ->first();

            if ($newTier && $newTier->id !== $lockedUser->loyalty_tier_id) {
                $oldTier = $lockedUser->loyaltyTier;
                $lockedUser->update(['loyalty_tier_id' => $newTier->id]);

                $lockedUser->auditEvent([
                    'request_id' => request()?->attributes?->get('request_id'),
                    'idempotency_key' => $lockedUser->id ? "loyalty_tier.change:user:{$lockedUser->id}:to:{$newTier->id}" : null,
                    'actor_user_id' => null,
                    'action' => 'user.loyalty_tier_changed',
                    'entity_type' => self::class,
                    'entity_id' => $lockedUser->id,
                    'meta' => [
                        'from_tier_id' => $oldTier?->id,
                        'from_tier_name' => $oldTier?->name,
                        'to_tier_id' => $newTier->id,
                        'to_tier_name' => $newTier->name,
                        'total_spend' => $lockedUser->total_spend,
                        'source' => 'updateTier',
                    ],
                ]);

                // Log tier upgrade for analytics
                /** @phpstan-ignore-next-line */
                \Illuminate\Support\Facades\Log::info('User tier upgraded', [
                    'user_id' => $lockedUser->id,
                    'old_tier' => $oldTier?->name,
                    'new_tier' => $newTier->name,
                    'total_spend' => $lockedUser->total_spend,
                ]);

                return $newTier;
            }

            return null;
        });
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

    /**
     * Determine if the user can access the Filament admin panel.
     * Only users with 'admin' or 'staff' roles can access.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Allow access only for admin and staff roles
        return in_array($this->role, ['admin', 'staff']);
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is staff.
     */
    public function isStaff(): bool
    {
        return in_array($this->role, ['admin', 'staff']);
    }
}

