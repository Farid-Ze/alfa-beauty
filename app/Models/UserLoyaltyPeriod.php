<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * UserLoyaltyPeriod Model
 * 
 * Tracks user spending per period for loyalty tier evaluation.
 * Enables yearly/quarterly tier reset instead of lifetime accumulation.
 */
class UserLoyaltyPeriod extends Model
{
        protected static function isUniqueConstraintViolation(QueryException $e): bool
        {
            $sqlState = $e->errorInfo[0] ?? null;
            $driverCode = $e->errorInfo[1] ?? null;

            // MySQL: SQLSTATE 23000 / driver 1062, Postgres: SQLSTATE 23505
            return $sqlState === '23000' || $sqlState === '23505' || $driverCode === 1062;
        }

    use HasFactory;

    protected $fillable = [
        'user_id',
        'loyalty_tier_id',
        'period_year',
        'period_quarter',
        'period_spend',
        'period_orders',
        'period_start',
        'period_end',
        'tier_qualified_at',
    ];

    protected $casts = [
        'period_year' => 'integer',
        'period_quarter' => 'integer',
        'period_spend' => 'decimal:2',
        'period_orders' => 'integer',
        'period_start' => 'date',
        'period_end' => 'date',
        'tier_qualified_at' => 'datetime',
    ];

    /* ─────────────────────────────────────────────────────────────
     * Relationships
     * ───────────────────────────────────────────────────────────── */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function loyaltyTier(): BelongsTo
    {
        return $this->belongsTo(LoyaltyTier::class);
    }

    /* ─────────────────────────────────────────────────────────────
     * Scopes
     * ───────────────────────────────────────────────────────────── */

    public function scopeForYear($query, int $year)
    {
        return $query->where('period_year', $year);
    }

    public function scopeForQuarter($query, int $quarter)
    {
        return $query->where('period_quarter', $quarter);
    }

    public function scopeCurrent($query)
    {
        $now = now();
        return $query->where('period_start', '<=', $now)
                     ->where('period_end', '>=', $now);
    }

    /* ─────────────────────────────────────────────────────────────
     * Static Factory Methods
     * ───────────────────────────────────────────────────────────── */

    public static function getOrCreateForUser(User $user, string $periodType = 'yearly'): self
    {
        $now = now();
        $year = $now->year;
        $quarter = $periodType === 'quarterly' ? (int) ceil($now->month / 3) : null;
        
        // Calculate period dates
        if ($periodType === 'quarterly') {
            $periodStart = $now->copy()->startOfQuarter();
            $periodEnd = $now->copy()->endOfQuarter();
        } else {
            $periodStart = $now->copy()->startOfYear();
            $periodEnd = $now->copy()->endOfYear();
        }
        
        $lookup = [
            'user_id' => $user->id,
            'period_year' => $year,
            'period_quarter' => $quarter,
        ];

        $defaults = [
            'loyalty_tier_id' => $user->loyalty_tier_id ?? LoyaltyTier::getDefaultTierId(),
            'period_spend' => 0,
            'period_orders' => 0,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ];

        return DB::transaction(function () use ($lookup, $defaults) {
            try {
                return self::firstOrCreate($lookup, $defaults);
            } catch (QueryException $e) {
                if (!self::isUniqueConstraintViolation($e)) {
                    throw $e;
                }

                return self::where($lookup)->firstOrFail();
            }
        });
    }

    /* ─────────────────────────────────────────────────────────────
     * Business Logic
     * ───────────────────────────────────────────────────────────── */

    public function addSpend(float $amount): void
    {
        DB::transaction(function () use ($amount) {
            /** @var self $locked */
            $locked = self::whereKey($this->id)->lockForUpdate()->firstOrFail();
            $locked->increment('period_spend', $amount);
            $locked->increment('period_orders');

            // Check if user qualifies for tier upgrade
            $locked->evaluateTierQualification();
        });
    }

    public function evaluateTierQualification(): void
    {
        DB::transaction(function () {
            /** @var self $lockedPeriod */
            $lockedPeriod = self::whereKey($this->id)->lockForUpdate()->firstOrFail();

            // NOTE: loyalty_tiers uses min_spend (lifetime threshold). The schema does not define min_total_spend.
            $qualifyingTier = LoyaltyTier::where('min_spend', '<=', $lockedPeriod->period_spend)
                ->orderByDesc('min_spend')
                ->first();

            if (!$qualifyingTier || $qualifyingTier->id === $lockedPeriod->loyalty_tier_id) {
                return;
            }

            $previousPeriodTierId = $lockedPeriod->loyalty_tier_id;
            $lockedPeriod->update([
                'loyalty_tier_id' => $qualifyingTier->id,
                'tier_qualified_at' => now(),
            ]);

            /** @var User|null $lockedUser */
            $lockedUser = User::whereKey($lockedPeriod->user_id)->lockForUpdate()->first();
            if (!$lockedUser) {
                return;
            }

            $previousUserTierId = $lockedUser->loyalty_tier_id;
            $lockedUser->update([
                'loyalty_tier_id' => $qualifyingTier->id,
                'tier_evaluated_at' => now(),
                'tier_valid_until' => now()->addMonths($qualifyingTier->tier_validity_months ?? 12),
                'current_period_spend' => $lockedPeriod->period_spend,
            ]);

            // Governance + traceability (non-fatal)
            $quarter = $lockedPeriod->period_quarter !== null ? (string) $lockedPeriod->period_quarter : 'year';
            $idempotencyKey = "loyalty_period.tier_qualified:user:{$lockedUser->id}:{$lockedPeriod->period_year}:{$quarter}:to:{$qualifyingTier->id}";

            $this->auditEvent([
                'request_id' => request()?->attributes?->get('request_id'),
                'idempotency_key' => $idempotencyKey,
                'actor_user_id' => null,
                'action' => 'user.loyalty_tier_changed',
                'entity_type' => User::class,
                'entity_id' => $lockedUser->id,
                'meta' => [
                    'source' => 'loyalty_period',
                    'period_year' => $lockedPeriod->period_year,
                    'period_quarter' => $lockedPeriod->period_quarter,
                    'period_spend' => $lockedPeriod->period_spend,
                    'from_user_tier_id' => $previousUserTierId,
                    'from_period_tier_id' => $previousPeriodTierId,
                    'to_tier_id' => $qualifyingTier->id,
                ],
            ]);
        });
    }

    protected function auditEvent(array $payload): void
    {
        try {
            if (!Schema::hasTable('audit_events')) {
                return;
            }

            AuditEvent::create($payload);
        } catch (\Throwable $e) {
            Log::warning('AuditEvent write failed', [
                'error' => $e->getMessage(),
                'action' => $payload['action'] ?? null,
                'entity_type' => $payload['entity_type'] ?? null,
                'entity_id' => $payload['entity_id'] ?? null,
            ]);
        }
    }

    public function getProgressToNextTierAttribute(): array
    {
        $nextTier = LoyaltyTier::where('min_spend', '>', $this->period_spend)
            ->orderBy('min_spend')
            ->first();
            
        if (!$nextTier) {
            return [
                'next_tier' => null,
                'amount_needed' => 0,
                'progress_percent' => 100,
            ];
        }
        
        $amountNeeded = $nextTier->min_spend - $this->period_spend;
        $progressPercent = ($this->period_spend / $nextTier->min_spend) * 100;
        
        return [
            'next_tier' => $nextTier,
            'amount_needed' => $amountNeeded,
            'progress_percent' => min(100, round($progressPercent, 1)),
        ];
    }
}
