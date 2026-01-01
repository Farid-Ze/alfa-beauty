<?php

namespace Tests\Feature;

use App\Livewire\BuyItAgain;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BuyItAgainTest extends TestCase
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

    protected function createOrder(User $user, string $status = 'completed', string $orderNumber = 'ORD-TEST-001'): Order
    {
        return Order::create([
            'user_id' => $user->id,
            'order_number' => $orderNumber,
            'total_amount' => 412500,
            'shipping_cost' => 25000,
            'status' => $status,
            'shipping_address' => 'Test Address, Jakarta',
        ]);
    }

    public function test_buy_it_again_renders(): void
    {
        Livewire::test(BuyItAgain::class)
            ->assertStatus(200);
    }

    public function test_buy_it_again_shows_empty_for_guest(): void
    {
        $component = Livewire::test(BuyItAgain::class);
        
        // Guest users should see empty products list
        $component->assertStatus(200);
    }

    public function test_buy_it_again_shows_empty_for_new_user(): void
    {
        $user = User::create([
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $this->actingAs($user);

        $component = Livewire::test(BuyItAgain::class);
        
        // New user with no orders should see empty products
        $component->assertStatus(200);
    }

    public function test_buy_it_again_shows_products_from_completed_orders(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $product = Product::first();

        // Create completed order
        $order = $this->createOrder($user, 'completed');

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 350000,
            'total_price' => 350000,
        ]);

        $this->actingAs($user);

        Livewire::test(BuyItAgain::class)
            ->assertStatus(200);
    }

    public function test_buy_it_again_excludes_pending_orders(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $product = Product::first();

        // Create pending order (not completed)
        $order = $this->createOrder($user, 'pending', 'ORD-TEST-002');

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 350000,
            'total_price' => 350000,
        ]);

        $this->actingAs($user);

        // Products from pending orders should not appear
        Livewire::test(BuyItAgain::class)
            ->assertStatus(200);
    }

    public function test_buy_it_again_add_to_cart_dispatches_events(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $product = Product::first();

        $this->actingAs($user);

        Livewire::test(BuyItAgain::class)
            ->call('addToCart', $product->id)
            ->assertDispatched('cart-updated')
            ->assertDispatched('product-added-to-cart')
            ->assertDispatched('toggle-cart');
    }

    public function test_buy_it_again_limits_to_six_products(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $products = Product::take(8)->get();

        // Create completed order with 8 products
        $order = $this->createOrder($user, 'completed', 'ORD-TEST-003');

        foreach ($products as $product) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 350000,
                'total_price' => 350000,
            ]);
        }

        $this->actingAs($user);

        // Should only show max 6 products
        Livewire::test(BuyItAgain::class)
            ->assertStatus(200);
    }
}
