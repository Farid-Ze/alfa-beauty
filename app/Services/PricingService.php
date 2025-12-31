<?php

namespace App\Services;

use App\Models\CustomerPriceList;
use App\Models\Product;
use App\Models\ProductPriceTier;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * PricingService
 * 
 * Centralized pricing logic for B2B e-commerce.
 * Handles customer-specific pricing, volume tiers, and loyalty discounts.
 * 
 * PRIORITY ORDER:
 * 1. Customer-specific price (highest priority)
 * 2. Volume tier pricing
 * 3. Loyalty tier discount
 * 4. Base price (fallback)
 * 
 * PERFORMANCE OPTIMIZATIONS:
 * - Bulk price lookups to avoid N+1 queries
 * - Caching for price lists
 * - Single database query for multiple products
 */
class PricingService
{
    /**
     * Cache TTL in seconds (5 minutes)
     */
    const CACHE_TTL = 300;

    /**
     * Get price for a single product for a user.
     *
     * @param Product $product
     * @param User|null $user
     * @param int $quantity Quantity for tier pricing
     * @return array ['price' => float, 'source' => string, 'discount_percent' => float|null]
     */
    public function getPrice(Product $product, ?User $user, int $quantity = 1): array
    {
        $basePrice = $product->base_price;
        
        // 1. Check customer-specific pricing
        if ($user) {
            $customerPrice = $this->getCustomerPrice($product, $user, $quantity);
            if ($customerPrice !== null) {
                return [
                    'price' => $customerPrice['price'],
                    'source' => 'customer_price_list',
                    'discount_percent' => $customerPrice['discount_percent'] ?? null,
                    'original_price' => $basePrice,
                ];
            }
        }

        // 2. Check volume tier pricing
        $tierPrice = $this->getTierPrice($product, $quantity);
        if ($tierPrice !== null) {
            return [
                'price' => $tierPrice['price'],
                'source' => 'volume_tier',
                'discount_percent' => $tierPrice['discount_percent'] ?? null,
                'tier_name' => $tierPrice['tier_name'] ?? null,
                'original_price' => $basePrice,
            ];
        }

        // 3. Check loyalty tier discount
        if ($user && $user->loyaltyTier) {
            $tierDiscount = $user->loyaltyTier->discount_percent;
            if ($tierDiscount > 0) {
                return [
                    'price' => $basePrice * (1 - $tierDiscount / 100),
                    'source' => 'loyalty_tier',
                    'discount_percent' => $tierDiscount,
                    'tier_name' => $user->loyaltyTier->name,
                    'original_price' => $basePrice,
                ];
            }
        }

        // 4. Return base price
        return [
            'price' => $basePrice,
            'source' => 'base_price',
            'discount_percent' => null,
            'original_price' => $basePrice,
        ];
    }

    /**
     * BULK: Get prices for multiple products at once.
     * 
     * CRITICAL: This method prevents N+1 queries when calculating cart totals.
     * Use this for cart operations instead of calling getPrice() in a loop.
     *
     * @param Collection|array $products Collection of products or array of [product_id => quantity]
     * @param User|null $user
     * @return array [product_id => ['price' => float, 'source' => string, ...]]
     */
    public function getBulkPrices($products, ?User $user): array
    {
        $prices = [];
        
        // Normalize input
        if ($products instanceof Collection) {
            $productIds = $products->pluck('id')->toArray();
            $quantities = $products->pluck('quantity', 'id')->toArray();
            $productModels = $products->keyBy('id');
        } else {
            $productIds = array_keys($products);
            $quantities = $products;
            $productModels = Product::whereIn('id', $productIds)->get()->keyBy('id');
        }

        // Early return if no products
        if (empty($productIds)) {
            return [];
        }

        // 1. Bulk load customer price lists (single query)
        $customerPrices = $this->bulkLoadCustomerPrices($productIds, $user);
        
        // 2. Bulk load volume tiers (single query)
        $volumeTiers = $this->bulkLoadVolumeTiers($productIds);
        
        // 3. Get loyalty tier discount
        $loyaltyDiscount = $user?->loyaltyTier?->discount_percent ?? 0;
        $loyaltyTierName = $user?->loyaltyTier?->name;

        // 4. Calculate prices for each product
        foreach ($productIds as $productId) {
            $product = $productModels[$productId] ?? null;
            if (!$product) continue;

            $basePrice = $product->base_price;
            $quantity = $quantities[$productId] ?? 1;

            // Check customer-specific price first
            $customerPrice = $this->resolveCustomerPrice(
                $customerPrices[$productId] ?? [],
                $product,
                $quantity
            );
            
            if ($customerPrice !== null) {
                $prices[$productId] = [
                    'price' => $customerPrice['price'],
                    'source' => 'customer_price_list',
                    'discount_percent' => $customerPrice['discount_percent'],
                    'original_price' => $basePrice,
                ];
                continue;
            }

            // Check volume tier
            $tier = $this->resolveVolumeTier(
                $volumeTiers[$productId] ?? [],
                $quantity,
                $basePrice
            );
            
            if ($tier !== null) {
                $prices[$productId] = [
                    'price' => $tier['price'],
                    'source' => 'volume_tier',
                    'discount_percent' => $tier['discount_percent'],
                    'tier_name' => "{$tier['min_qty']}+",
                    'original_price' => $basePrice,
                ];
                continue;
            }

            // Check loyalty discount
            if ($loyaltyDiscount > 0) {
                $prices[$productId] = [
                    'price' => $basePrice * (1 - $loyaltyDiscount / 100),
                    'source' => 'loyalty_tier',
                    'discount_percent' => $loyaltyDiscount,
                    'tier_name' => $loyaltyTierName,
                    'original_price' => $basePrice,
                ];
                continue;
            }

            // Base price
            $prices[$productId] = [
                'price' => $basePrice,
                'source' => 'base_price',
                'discount_percent' => null,
                'original_price' => $basePrice,
            ];
        }

        return $prices;
    }

    /**
     * Get customer-specific price for a product.
     * 
     * NOTE: Uses try-catch to gracefully handle missing B2B columns.
     */
    protected function getCustomerPrice(Product $product, User $user, int $quantity): ?array
    {
        $cacheKey = "customer_price:{$user->id}:{$product->id}";
        
        try {
            $priceList = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($product, $user) {
                return CustomerPriceList::where('user_id', $user->id)
                    ->forProduct($product)
                    ->valid()
                    ->orderByDesc('priority')
                    ->orderByDesc('product_id')
                    ->orderByDesc('brand_id')
                    ->orderByDesc('category_id')
                    ->first();
            });
        } catch (\Exception $e) {
            // Fallback: Simple product_id-only query
            Cache::forget($cacheKey);
            $priceList = CustomerPriceList::where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->valid()
                ->first();
        }

        if (!$priceList) {
            return null;
        }

        // Check min quantity requirement (may not exist, use null coalescing)
        $minQty = $priceList->min_quantity ?? 1;
        if ($quantity < $minQty) {
            return null;
        }

        return [
            'price' => $priceList->calculatePrice($product->base_price),
            'discount_percent' => $priceList->discount_percent ?? 0,
        ];
    }

    /**
     * Get volume tier price for a product.
     */
    protected function getTierPrice(Product $product, int $quantity): ?array
    {
        $tier = ProductPriceTier::where('product_id', $product->id)
            ->forQuantity($quantity)
            ->orderByDesc('min_quantity') // Highest applicable tier
            ->first();

        if (!$tier) {
            return null;
        }

        return [
            'price' => $tier->calculateUnitPrice($product->base_price),
            'discount_percent' => $tier->discount_percent,
            'tier_name' => "{$tier->min_quantity}+",
        ];
    }

    /**
     * Bulk load customer prices for multiple products.
     * 
     * NOTE: Uses try-catch to gracefully handle missing B2B columns
     * (brand_id, category_id, priority) that may not exist in database.
     */
    protected function bulkLoadCustomerPrices(array $productIds, ?User $user): array
    {
        if (!$user) {
            return [];
        }

        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        try {
            // Full B2B hierarchy query (requires brand_id, category_id, priority columns)
            $brandIds = $products->pluck('brand_id')->unique()->filter()->toArray();
            $categoryIds = $products->pluck('category_id')->unique()->filter()->toArray();

            $priceLists = CustomerPriceList::where('user_id', $user->id)
                ->valid()
                ->where(function ($q) use ($productIds, $brandIds, $categoryIds) {
                    $q->whereIn('product_id', $productIds)
                      ->orWhereIn('brand_id', $brandIds)
                      ->orWhereIn('category_id', $categoryIds)
                      ->orWhere(function ($q2) {
                          $q2->whereNull('product_id')
                             ->whereNull('brand_id')
                             ->whereNull('category_id');
                      });
                })
                ->orderByDesc('priority')
                ->get();

            // Group by product with full hierarchy matching
            $grouped = [];
            foreach ($productIds as $productId) {
                $product = $products[$productId] ?? null;
                if (!$product) continue;

                $grouped[$productId] = $priceLists->filter(function ($pl) use ($product) {
                    return $pl->product_id === $product->id
                        || $pl->brand_id === $product->brand_id
                        || $pl->category_id === $product->category_id
                        || ($pl->product_id === null && $pl->brand_id === null && $pl->category_id === null);
                })->values()->toArray();
            }

            return $grouped;

        } catch (\Exception $e) {
            // Fallback: Simple product_id-only query (works with basic schema)
            \Illuminate\Support\Facades\Log::warning('B2B pricing fallback: ' . $e->getMessage());

            $priceLists = CustomerPriceList::where('user_id', $user->id)
                ->valid()
                ->whereIn('product_id', $productIds)
                ->get();

            $grouped = [];
            foreach ($productIds as $productId) {
                $grouped[$productId] = $priceLists->filter(function ($pl) use ($productId) {
                    return $pl->product_id === $productId;
                })->values()->toArray();
            }

            return $grouped;
        }
    }

    /**
     * Bulk load volume tiers for multiple products.
     */
    protected function bulkLoadVolumeTiers(array $productIds): array
    {
        $tiers = ProductPriceTier::whereIn('product_id', $productIds)
            ->orderBy('product_id')
            ->orderByDesc('min_quantity')
            ->get()
            ->groupBy('product_id')
            ->toArray();

        return $tiers;
    }

    /**
     * Resolve the best customer price from a list.
     */
    protected function resolveCustomerPrice(array $priceLists, Product $product, int $quantity): ?array
    {
        foreach ($priceLists as $pl) {
            if ($quantity >= ($pl['min_quantity'] ?? 1)) {
                $price = $pl['custom_price'] 
                    ?? ($product->base_price * (1 - ($pl['discount_percent'] ?? 0) / 100));
                
                return [
                    'price' => $price,
                    'discount_percent' => $pl['discount_percent'] ?? null,
                ];
            }
        }
        return null;
    }

    /**
     * Resolve the best volume tier from a list.
     */
    protected function resolveVolumeTier(array $tiers, int $quantity, float $basePrice): ?array
    {
        foreach ($tiers as $tier) {
            $minQty = $tier['min_quantity'];
            $maxQty = $tier['max_quantity'];
            
            if ($quantity >= $minQty && ($maxQty === null || $quantity <= $maxQty)) {
                $price = $tier['unit_price'] 
                    ?? ($basePrice * (1 - ($tier['discount_percent'] ?? 0) / 100));
                
                return [
                    'price' => $price,
                    'discount_percent' => $tier['discount_percent'] ?? null,
                    'min_qty' => $minQty,
                ];
            }
        }
        return null;
    }

    /**
     * Clear pricing cache for a user.
     */
    public function clearCacheForUser(int $userId): void
    {
        // In production, use tagged caching or a more sophisticated approach
        Cache::flush(); // Simple approach for now
    }

    /**
     * Clear pricing cache for a product.
     */
    public function clearCacheForProduct(int $productId): void
    {
        Cache::flush(); // Simple approach for now
    }
}
