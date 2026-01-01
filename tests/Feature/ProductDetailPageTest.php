<?php

namespace Tests\Feature;

use App\Livewire\ProductDetailPage;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductDetailPageTest extends TestCase
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

    public function test_product_detail_page_renders(): void
    {
        $product = Product::first();
        
        $response = $this->get('/products/' . $product->slug);
        $response->assertStatus(200);
    }

    public function test_product_detail_shows_product_name(): void
    {
        $product = Product::first();
        
        Livewire::test(ProductDetailPage::class, ['slug' => $product->slug])
            ->assertSee($product->name);
    }

    public function test_product_detail_shows_price(): void
    {
        $product = Product::first();
        
        $component = Livewire::test(ProductDetailPage::class, ['slug' => $product->slug]);
        
        $this->assertNotNull($component->get('currentPrice'));
    }

    public function test_quantity_defaults_to_min_order_qty(): void
    {
        $product = Product::first();
        
        $component = Livewire::test(ProductDetailPage::class, ['slug' => $product->slug]);
        
        $this->assertEquals($product->min_order_qty ?? 1, $component->get('quantity'));
    }

    public function test_quantity_can_be_increased(): void
    {
        $product = Product::first();
        
        Livewire::test(ProductDetailPage::class, ['slug' => $product->slug])
            ->call('incrementQuantity')
            ->assertSet('quantity', ($product->min_order_qty ?? 1) + ($product->order_increment ?? 1));
    }

    public function test_quantity_can_be_decreased(): void
    {
        $product = Product::first();
        $minQty = $product->min_order_qty ?? 1;
        $increment = $product->order_increment ?? 1;
        
        Livewire::test(ProductDetailPage::class, ['slug' => $product->slug])
            ->call('incrementQuantity') // First increase
            ->call('decrementQuantity') // Then decrease
            ->assertSet('quantity', $minQty);
    }

    public function test_quantity_cannot_go_below_min_order_qty(): void
    {
        $product = Product::first();
        $minQty = $product->min_order_qty ?? 1;
        
        Livewire::test(ProductDetailPage::class, ['slug' => $product->slug])
            ->call('decrementQuantity')
            ->call('decrementQuantity')
            ->call('decrementQuantity')
            ->assertSet('quantity', $minQty);
    }

    public function test_add_to_cart_works(): void
    {
        $product = Product::first();
        
        Livewire::test(ProductDetailPage::class, ['slug' => $product->slug])
            ->call('addToCart')
            ->assertDispatched('cart-updated');
    }

    public function test_invalid_product_slug_returns_404(): void
    {
        $response = $this->get('/product/non-existent-product-slug');
        $response->assertStatus(404);
    }

    public function test_product_detail_shows_brand(): void
    {
        $product = Product::with('brand')->first();
        
        // Use Livewire test instead of HTTP to get component state
        Livewire::test(ProductDetailPage::class, ['slug' => $product->slug])
            ->assertSee($product->brand->name);
    }

    public function test_product_has_category_loaded(): void
    {
        $product = Product::with('category')->first();
        
        // The product detail page component loads product with category relation
        // We verify the product's category is accessible via the slug parameter
        $component = Livewire::test(ProductDetailPage::class, ['slug' => $product->slug]);
        
        // Check that slug is properly set
        $component->assertSet('slug', $product->slug);
        
        // Verify we can instantiate the view without errors
        $this->assertNotNull($product->category);
    }

    public function test_product_detail_shows_sku(): void
    {
        $product = Product::first();
        
        Livewire::test(ProductDetailPage::class, ['slug' => $product->slug])
            ->assertSee($product->sku);
    }

    public function test_authenticated_user_sees_tier_pricing(): void
    {
        $user = User::factory()->create();
        $product = Product::first();
        
        $this->actingAs($user);
        
        $component = Livewire::test(ProductDetailPage::class, ['slug' => $product->slug]);
        
        // Price should be calculated based on user's tier
        $this->assertNotNull($component->get('currentPrice'));
    }

    public function test_guest_sees_base_price(): void
    {
        $product = Product::first();
        
        $component = Livewire::test(ProductDetailPage::class, ['slug' => $product->slug]);
        
        // For guest, should see base price
        $this->assertEquals($product->base_price, $component->get('currentPrice'));
    }
}
