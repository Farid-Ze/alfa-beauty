<?php

namespace Tests\Feature;

use App\Livewire\CartCounter;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CartCounterTest extends TestCase
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

    public function test_cart_counter_renders(): void
    {
        Livewire::test(CartCounter::class)
            ->assertStatus(200);
    }

    public function test_cart_counter_shows_zero_for_empty_cart(): void
    {
        Livewire::test(CartCounter::class)
            ->assertSee('0');
    }

    public function test_cart_counter_shows_item_count_for_authenticated_user(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $product = Product::first();
        
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $this->actingAs($user);

        Livewire::test(CartCounter::class)
            ->assertSee('3');
    }

    public function test_cart_counter_sums_multiple_items(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $products = Product::take(2)->get();
        
        $cart = Cart::create(['user_id' => $user->id]);
        
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $products[0]->id,
            'quantity' => 2,
        ]);
        
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $products[1]->id,
            'quantity' => 3,
        ]);

        $this->actingAs($user);

        Livewire::test(CartCounter::class)
            ->assertSee('5');
    }

    public function test_cart_counter_updates_on_cart_updated_event(): void
    {
        Livewire::test(CartCounter::class)
            ->dispatch('cart-updated')
            ->assertStatus(200);
    }
}
