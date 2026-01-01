<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\LoyaltyTierSeeder::class);
        $this->seed(\Database\Seeders\BrandSeeder::class);
        $this->seed(\Database\Seeders\CategorySeeder::class);
        $this->seed(\Database\Seeders\ProductSeeder::class);
    }

    public function test_product_belongs_to_brand(): void
    {
        $product = Product::first();

        $this->assertInstanceOf(Brand::class, $product->brand);
        $this->assertNotNull($product->brand->id);
    }

    public function test_product_belongs_to_category(): void
    {
        $product = Product::first();

        $this->assertInstanceOf(Category::class, $product->category);
        $this->assertNotNull($product->category->id);
    }

    public function test_product_base_price_cast_to_float(): void
    {
        $product = Product::first();

        $this->assertIsFloat($product->base_price);
        $this->assertGreaterThan(0, $product->base_price);
    }

    public function test_product_is_active_cast_to_boolean(): void
    {
        $product = Product::first();

        $this->assertIsBool($product->is_active);
    }

    public function test_product_has_min_order_qty(): void
    {
        $product = Product::first();

        // min_order_qty should be >= 1
        $this->assertTrue($product->min_order_qty >= 1);
    }

    public function test_product_can_have_price_tiers(): void
    {
        $product = Product::first();

        $this->assertIsIterable($product->priceTiers);
    }

    public function test_product_has_sku(): void
    {
        $product = Product::first();

        $this->assertNotNull($product->sku);
        $this->assertIsString($product->sku);
    }

    public function test_product_has_slug(): void
    {
        $product = Product::first();

        $this->assertNotNull($product->slug);
        $this->assertIsString($product->slug);
    }
}
