<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
