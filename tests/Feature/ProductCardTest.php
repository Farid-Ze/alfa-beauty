<?php

namespace Tests\Feature;

use App\Livewire\ProductCard;
use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductCardTest extends TestCase
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

    public function test_product_card_renders(): void
    {
        $product = Product::first();

        Livewire::test(ProductCard::class, ['product' => $product])
            ->assertStatus(200);
    }

    public function test_product_card_displays_product_name(): void
    {
        $product = Product::first();

        Livewire::test(ProductCard::class, ['product' => $product])
            ->assertSee($product->name);
    }

    public function test_product_card_displays_brand(): void
    {
        $product = Product::with('brand')->first();

        Livewire::test(ProductCard::class, ['product' => $product])
            ->assertSee($product->brand->name);
    }

    public function test_product_card_accepts_price_info(): void
    {
        $product = Product::first();
        $priceInfo = [
            'price' => 350000,
            'original_price' => 400000,
            'source' => 'volume_tier',
            'discount_percent' => 12.5,
        ];

        $component = Livewire::test(ProductCard::class, [
            'product' => $product,
            'priceInfo' => $priceInfo,
        ]);

        $this->assertEquals($priceInfo, $component->get('priceInfo'));
    }

    public function test_product_card_add_to_cart_for_guest(): void
    {
        $product = Product::first();

        Livewire::test(ProductCard::class, ['product' => $product])
            ->call('addToCart')
            ->assertDispatched('cart-updated')
            ->assertDispatched('product-added-to-cart')
            ->assertDispatched('toggle-cart');
    }

    public function test_product_card_add_to_cart_for_authenticated_user(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $product = Product::first();

        $this->actingAs($user);

        Livewire::test(ProductCard::class, ['product' => $product])
            ->call('addToCart')
            ->assertDispatched('cart-updated')
            ->assertDispatched('product-added-to-cart');

        // Verify cart was created
        $this->assertDatabaseHas('carts', ['user_id' => $user->id]);
    }

    public function test_product_card_dispatches_product_name_on_add(): void
    {
        $product = Product::first();

        Livewire::test(ProductCard::class, ['product' => $product])
            ->call('addToCart')
            ->assertDispatched('product-added-to-cart', name: $product->name);
    }
}
