<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\CustomerPriceList;
use App\Models\Product;
use App\Models\ProductPriceTier;
use App\Models\User;
use App\Services\PricingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PricingService $pricingService;
    protected Product $product;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed required data
        $this->seed(\Database\Seeders\LoyaltyTierSeeder::class);
        $this->seed(\Database\Seeders\BrandSeeder::class);
        $this->seed(\Database\Seeders\CategorySeeder::class);
        
        $this->pricingService = app(PricingService::class);
        
        // Create product
        $this->product = Product::factory()->create([
            'name' => 'Test Product',
            'base_price' => 100000, // Rp 100,000
            'stock' => 100,
        ]);

        // Create user
        $this->user = User::factory()->create([
            'name' => 'Test Customer',
            'loyalty_tier_id' => 1, // Bronze tier
        ]);
    }

    public function test_returns_base_price_for_guest(): void
    {
        $result = $this->pricingService->getPrice($this->product, null);

        $this->assertEquals(100000, $result['price']);
        $this->assertEquals('base_price', $result['source']);
        $this->assertNull($result['discount_percent']);
    }

    public function test_returns_base_price_for_user_without_special_pricing(): void
    {
        $result = $this->pricingService->getPrice($this->product, $this->user);

        // Should return base price (no loyalty tier discount for tier 1)
        $this->assertEquals(100000, $result['price']);
        $this->assertEquals('base_price', $result['source']);
    }

    public function test_customer_specific_fixed_price(): void
    {
        // Create customer-specific price
        CustomerPriceList::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'custom_price' => 80000, // Fixed price Rp 80,000
            'min_quantity' => 1,
            'priority' => 0,
        ]);

        $result = $this->pricingService->getPrice($this->product, $this->user);

        $this->assertEquals(80000, $result['price']);
        $this->assertEquals('customer_price_list', $result['source']);
        $this->assertEquals(100000, $result['original_price']);
    }

    public function test_customer_specific_discount_percent(): void
    {
        // Create 20% discount
        CustomerPriceList::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'discount_percent' => 20,
            'min_quantity' => 1,
            'priority' => 0,
        ]);

        $result = $this->pricingService->getPrice($this->product, $this->user);

        $this->assertEquals(80000, $result['price']); // 100k - 20%
        $this->assertEquals('customer_price_list', $result['source']);
        $this->assertEquals(20, $result['discount_percent']);
    }

    public function test_customer_price_min_quantity_requirement(): void
    {
        // Create discount only for 10+ units
        CustomerPriceList::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'discount_percent' => 15,
            'min_quantity' => 10,
            'priority' => 0,
        ]);

        // Order 5 units - shouldn't get discount
        $result1 = $this->pricingService->getPrice($this->product, $this->user, 5);
        $this->assertEquals(100000, $result1['price']);
        $this->assertEquals('base_price', $result1['source']);

        // Order 15 units - should get discount
        $result2 = $this->pricingService->getPrice($this->product, $this->user, 15);
        $this->assertEquals(85000, $result2['price']); // 100k - 15%
        $this->assertEquals('customer_price_list', $result2['source']);
    }

    public function test_brand_level_discount(): void
    {
        // Create brand-level discount
        CustomerPriceList::create([
            'user_id' => $this->user->id,
            'brand_id' => $this->product->brand_id,
            'discount_percent' => 10,
            'min_quantity' => 1,
            'priority' => 0,
        ]);

        $result = $this->pricingService->getPrice($this->product, $this->user);

        $this->assertEquals(90000, $result['price']); // 100k - 10%
        $this->assertEquals('customer_price_list', $result['source']);
    }

    public function test_price_validity_period(): void
    {
        // Create expired price
        CustomerPriceList::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'discount_percent' => 30,
            'min_quantity' => 1,
            'valid_until' => Carbon::yesterday(),
            'priority' => 0,
        ]);

        $result = $this->pricingService->getPrice($this->product, $this->user);

        // Should return base price (discount expired)
        $this->assertEquals(100000, $result['price']);
        $this->assertEquals('base_price', $result['source']);
    }

    public function test_volume_tier_pricing(): void
    {
        // Create volume tiers
        ProductPriceTier::create([
            'product_id' => $this->product->id,
            'min_quantity' => 1,
            'max_quantity' => 11,
            'discount_percent' => 0,
        ]);
        ProductPriceTier::create([
            'product_id' => $this->product->id,
            'min_quantity' => 12,
            'max_quantity' => 47,
            'discount_percent' => 10,
        ]);
        ProductPriceTier::create([
            'product_id' => $this->product->id,
            'min_quantity' => 48,
            'max_quantity' => null, // No upper limit
            'discount_percent' => 20,
        ]);

        // Test each tier
        $result1 = $this->pricingService->getPrice($this->product, null, 5);
        $this->assertEquals(100000, $result1['price']);

        $result2 = $this->pricingService->getPrice($this->product, null, 20);
        $this->assertEquals(90000, $result2['price']); // 10% off
        $this->assertEquals('volume_tier', $result2['source']);

        $result3 = $this->pricingService->getPrice($this->product, null, 100);
        $this->assertEquals(80000, $result3['price']); // 20% off
        $this->assertEquals('volume_tier', $result3['source']);
    }

    public function test_customer_price_takes_priority_over_volume_tier(): void
    {
        // Create volume tier
        ProductPriceTier::create([
            'product_id' => $this->product->id,
            'min_quantity' => 10,
            'max_quantity' => null,
            'discount_percent' => 15,
        ]);

        // Create customer-specific price (should take priority)
        CustomerPriceList::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'discount_percent' => 25,
            'min_quantity' => 1,
            'priority' => 0,
        ]);

        $result = $this->pricingService->getPrice($this->product, $this->user, 20);

        // Customer price (25%) should win over volume tier (15%)
        $this->assertEquals(75000, $result['price']);
        $this->assertEquals('customer_price_list', $result['source']);
    }

    public function test_bulk_prices_performance(): void
    {
        // Create 10 products
        $products = Product::factory()->count(10)->create(['base_price' => 50000]);
        
        // Add customer pricing for some
        foreach ($products->take(3) as $product) {
            CustomerPriceList::create([
                'user_id' => $this->user->id,
                'product_id' => $product->id,
                'discount_percent' => 10,
                'min_quantity' => 1,
                'priority' => 0,
            ]);
        }

        // Build input
        $productsWithQty = $products->map(function ($product) {
            $product->quantity = rand(1, 10);
            return $product;
        });

        // This should execute in constant queries, not N+1
        $result = $this->pricingService->getBulkPrices($productsWithQty, $this->user);

        $this->assertCount(10, $result);
        
        // First 3 should have customer pricing
        $customerPriced = collect($result)->filter(fn($p) => $p['source'] === 'customer_price_list');
        $this->assertCount(3, $customerPriced);
    }
}
