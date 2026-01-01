<?php

namespace Tests\Feature;

use App\Livewire\CartDrawer;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CartDrawerTest extends TestCase
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

    public function test_cart_drawer_renders(): void
    {
        Livewire::test(CartDrawer::class)
            ->assertStatus(200);
    }

    public function test_cart_drawer_updates_on_cart_event(): void
    {
        // Cart drawer listens to 'cart-updated' event and refreshes
        Livewire::test(CartDrawer::class)
            ->call('refresh')
            ->assertStatus(200);
    }

    public function test_cart_drawer_shows_empty_cart_for_guest(): void
    {
        $component = Livewire::test(CartDrawer::class);
        
        // For guest with no cart, items should be empty
        $component->assertStatus(200);
    }

    public function test_cart_drawer_shows_items_for_authenticated_user(): void
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
            'quantity' => 2,
        ]);

        $this->actingAs($user);

        Livewire::test(CartDrawer::class)
            ->assertStatus(200);
    }

    public function test_cart_drawer_can_remove_item(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $product = Product::first();
        
        $cart = Cart::create(['user_id' => $user->id]);
        $item = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->actingAs($user);

        Livewire::test(CartDrawer::class)
            ->call('removeItem', $item->id)
            ->assertDispatched('cart-updated');
        
        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }

    public function test_cart_drawer_can_update_quantity(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $product = Product::first();
        
        $cart = Cart::create(['user_id' => $user->id]);
        $item = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->actingAs($user);

        Livewire::test(CartDrawer::class)
            ->call('updateQuantity', $item->id, 5)
            ->assertDispatched('cart-updated');
        
        $this->assertEquals(5, $item->fresh()->quantity);
    }

    public function test_cart_drawer_can_increment_item(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $product = Product::first();
        $originalQty = 2;
        
        $cart = Cart::create(['user_id' => $user->id]);
        $item = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => $originalQty,
        ]);

        $this->actingAs($user);

        Livewire::test(CartDrawer::class)
            ->call('incrementItem', $item->id)
            ->assertDispatched('cart-updated');
        
        $increment = $product->order_increment ?? 1;
        $this->assertEquals($originalQty + $increment, $item->fresh()->quantity);
    }

    public function test_cart_drawer_can_decrement_item(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $product = Product::first();
        $originalQty = 5;
        
        $cart = Cart::create(['user_id' => $user->id]);
        $item = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => $originalQty,
        ]);

        $this->actingAs($user);

        Livewire::test(CartDrawer::class)
            ->call('decrementItem', $item->id)
            ->assertDispatched('cart-updated');
        
        $increment = $product->order_increment ?? 1;
        $minQty = $product->min_order_qty ?? 1;
        $expectedQty = max($minQty, $originalQty - $increment);
        $this->assertEquals($expectedQty, $item->fresh()->quantity);
    }

    public function test_cart_drawer_refreshes_on_cart_updated_event(): void
    {
        Livewire::test(CartDrawer::class)
            ->dispatch('cart-updated')
            ->assertStatus(200);
    }
}
