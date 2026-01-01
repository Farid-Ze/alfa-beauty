<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Contract for pricing calculation services.
 *
 * Defines the interface for calculating product prices based on
 * quantity tiers, loyalty discounts, and promotional rules.
 */
interface PricingServiceInterface
{
    /**
     * Calculate the unit price for a product based on quantity and user tier.
     *
     * @param Product $product The product to price
     * @param int $quantity The quantity being purchased
     * @param User|null $user The authenticated user (for tier discounts)
     * @return float The calculated unit price
     */
    public function calculatePrice(Product $product, int $quantity = 1, ?User $user = null): float;

    /**
     * Calculate prices for multiple products at once (bulk operation).
     *
     * @param Collection $products Collection of products with quantities
     * @param User|null $user The authenticated user
     * @return array Array of product IDs mapped to calculated prices
     */
    public function calculateBulkPrices(Collection $products, ?User $user = null): array;

    /**
     * Get the applicable discount percentage for a user.
     *
     * @param User|null $user The user to check
     * @return float Discount percentage (0-100)
     */
    public function getUserDiscount(?User $user): float;

    /**
     * Get volume tier pricing for a product.
     *
     * @param Product $product The product
     * @return array Array of quantity thresholds and prices
     */
    public function getVolumeTiers(Product $product): array;
}
