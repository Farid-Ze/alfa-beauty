<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * UserLoyaltyPeriod Model
 * 
 * Tracks user spending per period for loyalty tier evaluation.
 * Enables yearly/quarterly tier reset instead of lifetime accumulation.
 */
class UserLoyaltyPeriod extends Model
{
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
        $quarter = $periodType === 'quarterly' ? ceil($now->month / 3) : null;
        
        $existing = self::where('user_id', $user->id)
            ->where('period_year', $year)
            ->where('period_quarter', $quarter)
            ->first();
            
        if ($existing) {
            return $existing;
        }
        
        // Calculate period dates
        if ($periodType === 'quarterly') {
            $periodStart = $now->copy()->startOfQuarter();
            $periodEnd = $now->copy()->endOfQuarter();
        } else {
            $periodStart = $now->copy()->startOfYear();
            $periodEnd = $now->copy()->endOfYear();
        }
        
        return self::create([
            'user_id' => $user->id,
            'loyalty_tier_id' => $user->loyalty_tier_id ?? LoyaltyTier::getDefaultTierId(),
            'period_year' => $year,
            'period_quarter' => $quarter,
            'period_spend' => 0,
            'period_orders' => 0,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ]);
    }

    /* ─────────────────────────────────────────────────────────────
     * Business Logic
     * ───────────────────────────────────────────────────────────── */

    public function addSpend(float $amount): void
    {
        $this->increment('period_spend', $amount);
        $this->increment('period_orders');
        
        // Check if user qualifies for tier upgrade
        $this->evaluateTierQualification();
    }

    public function evaluateTierQualification(): void
    {
        $qualifyingTier = LoyaltyTier::where('min_total_spend', '<=', $this->period_spend)
            ->orderByDesc('min_total_spend')
            ->first();
            
        if ($qualifyingTier && $qualifyingTier->id !== $this->loyalty_tier_id) {
            $this->update([
                'loyalty_tier_id' => $qualifyingTier->id,
                'tier_qualified_at' => now(),
            ]);
            
            // Update user's tier
            $this->user->update([
                'loyalty_tier_id' => $qualifyingTier->id,
                'tier_evaluated_at' => now(),
                'tier_valid_until' => now()->addMonths($qualifyingTier->tier_validity_months ?? 12),
                'current_period_spend' => $this->period_spend,
            ]);
        }
    }

    public function getProgressToNextTierAttribute(): array
    {
        $nextTier = LoyaltyTier::where('min_total_spend', '>', $this->period_spend)
            ->orderBy('min_total_spend')
            ->first();
            
        if (!$nextTier) {
            return [
                'next_tier' => null,
                'amount_needed' => 0,
                'progress_percent' => 100,
            ];
        }
        
        $amountNeeded = $nextTier->min_total_spend - $this->period_spend;
        $progressPercent = ($this->period_spend / $nextTier->min_total_spend) * 100;
        
        return [
            'next_tier' => $nextTier,
            'amount_needed' => $amountNeeded,
            'progress_percent' => min(100, round($progressPercent, 1)),
        ];
    }
}
