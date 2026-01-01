<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LoyaltyTierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /**
         * Tier System - Updated 2025 (Industry Research)
         * 
         * Values based on:
         * - Indonesian B2B salon market benchmarks
         * - Global e-commerce loyalty standards (2024-2025)
         * - Accounting safe point liability (1% of revenue)
         * 
         * @see docs/implementation_plan.md for detailed rationale
         */
        $tiers = [
            [
                'name' => 'Guest',
                'slug' => 'guest',
                'min_spend' => 0,
                'discount_percent' => 0,
                'point_multiplier' => 1.0, // Now guests can earn points (1x base rate)
                'free_shipping' => false,
                'badge_color' => '#6B7280', // Gray
                'period_type' => 'yearly',
                'tier_validity_months' => 12,
                'auto_downgrade' => false, // Guest never downgrades
            ],
            [
                'name' => 'Silver',
                'slug' => 'silver',
                'min_spend' => 5000000, // Rp 5 juta/year - accessible for small salons
                'discount_percent' => 5,
                'point_multiplier' => 1.0,
                'free_shipping' => false, // Requires min order Rp 2.5 juta
                'badge_color' => '#94A3B8', // Silver
                'period_type' => 'yearly',
                'tier_validity_months' => 12,
                'auto_downgrade' => true,
            ],
            [
                'name' => 'Gold',
                'slug' => 'gold',
                'min_spend' => 25000000, // Rp 25 juta/year - medium salons
                'discount_percent' => 10,
                'point_multiplier' => 1.5,
                'free_shipping' => true, // Always free
                'badge_color' => '#C9A962', // Gold
                'period_type' => 'yearly',
                'tier_validity_months' => 12,
                'auto_downgrade' => true,
            ],
            [
                'name' => 'Platinum',
                'slug' => 'platinum',
                'min_spend' => 75000000, // Rp 75 juta/year - high-value clients
                'discount_percent' => 12,
                'point_multiplier' => 2.0,
                'free_shipping' => true,
                'badge_color' => '#E5E4E2', // Platinum
                'period_type' => 'yearly',
                'tier_validity_months' => 12,
                'auto_downgrade' => true,
            ],
        ];

        foreach ($tiers as $tier) {
            \App\Models\LoyaltyTier::updateOrCreate(
                ['slug' => $tier['slug']], // Find by slug
                $tier // Update or create with these values
            );
        }
    }
}
