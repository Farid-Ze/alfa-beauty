<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * LoyaltyTier Model
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property float $min_spend
 * @property float $discount_percent
 * @property float $point_multiplier
 * @property bool $free_shipping
 * @property string|null $badge_color
 * @property string|null $period_type
 * @property int|null $tier_validity_months
 * @property bool $auto_downgrade
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|User[] $users
 */
class LoyaltyTier extends Model
{
    /** @use HasFactory<\Database\Factories\LoyaltyTierFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'min_spend',
        'discount_percent',
        'point_multiplier',
        'free_shipping',
        'badge_color',
        'period_type',
        'tier_validity_months',
        'auto_downgrade',
    ];

    protected $casts = [
        'min_spend' => 'float',
        'discount_percent' => 'float',
        'point_multiplier' => 'float',
        'free_shipping' => 'boolean',
        'tier_validity_months' => 'integer',
        'auto_downgrade' => 'boolean',
    ];

    /* ─────────────────────────────────────────────────────────────
     * Constants
     * ───────────────────────────────────────────────────────────── */

    public const PERIOD_TYPES = [
        'lifetime' => 'Seumur hidup',
        'yearly' => 'Tahunan',
        'quarterly' => 'Kuartalan',
        'monthly' => 'Bulanan',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function loyaltyPeriods(): HasMany
    {
        return $this->hasMany(UserLoyaltyPeriod::class);
    }

    public function moqOverrides(): HasMany
    {
        return $this->hasMany(ProductMoqOverride::class);
    }

    /* ─────────────────────────────────────────────────────────────
     * Static Helpers
     * ───────────────────────────────────────────────────────────── */

    /**
     * Get the default (lowest) tier ID.
     */
    public static function getDefaultTierId(): ?int
    {
        return self::orderBy('min_spend')->first()?->id;
    }

    /**
     * Get tier by spend amount.
     */
    public static function getTierBySpend(float $totalSpend): ?self
    {
        return self::where('min_spend', '<=', $totalSpend)
            ->orderByDesc('min_spend')
            ->first();
    }

    /**
     * Get next tier after this one.
     */
    public function getNextTierAttribute(): ?self
    {
        return self::where('min_spend', '>', $this->min_spend)
            ->orderBy('min_spend')
            ->first();
    }

    /**
     * Get period type label.
     */
    public function getPeriodTypeLabelAttribute(): string
    {
        return self::PERIOD_TYPES[$this->period_type] ?? $this->period_type;
    }
}
