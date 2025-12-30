<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed required data
        $this->seed(\Database\Seeders\LoyaltyTierSeeder::class);
        $this->seed(\Database\Seeders\BrandSeeder::class);
        $this->seed(\Database\Seeders\CategorySeeder::class);
        $this->seed(\Database\Seeders\ProductSeeder::class);
    }

    public function test_guest_cannot_access_checkout_with_empty_cart(): void
    {
        $response = $this->get('/checkout');

        $response->assertRedirect('/');
    }

    public function test_user_can_access_checkout_with_items_in_cart(): void
    {
        $product = Product::first();
        $cartService = app(CartService::class);
        $cartService->addItem($product->id, 1);

        $response = $this->get('/checkout');

        $response->assertStatus(200);
    }

    public function test_order_is_created_from_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::first();
        
        $this->actingAs($user);
        
        $cartService = app(CartService::class);
        $cartService->addItem($product->id, 2);
        
        $cart = $cartService->getCart();
        $orderService = app(OrderService::class);
        
        $order = $orderService->createFromCart($cart, [
            'name' => 'Test User',
            'phone' => '08123456789',
            'address' => 'Test Address 123, City',
            'notes' => 'Test notes',
        ], $user->id);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals($user->id, $order->user_id);
        $this->assertEquals(1, $order->items->count());
        $this->assertEquals(2, $order->items->first()->quantity);
        $this->assertStringStartsWith('ORD-', $order->order_number);
    }

    public function test_stock_is_reduced_after_order(): void
    {
        $user = User::factory()->create();
        $product = Product::first();
        $initialStock = $product->stock;
        
        $this->actingAs($user);
        
        $cartService = app(CartService::class);
        $cartService->addItem($product->id, 2);
        
        $cart = $cartService->getCart();
        $orderService = app(OrderService::class);
        
        $orderService->createFromCart($cart, [
            'name' => 'Test User',
            'phone' => '08123456789',
            'address' => 'Test Address 123, City',
            'notes' => '',
        ], $user->id);

        $product->refresh();
        $this->assertEquals($initialStock - 2, $product->stock);
    }

    public function test_tier_discount_is_applied_for_silver_user(): void
    {
        $silverTier = \App\Models\LoyaltyTier::where('slug', 'silver')->first();
        $user = User::factory()->create([
            'loyalty_tier_id' => $silverTier->id,
        ]);
        $product = Product::first();
        
        $this->actingAs($user);
        
        $cartService = app(CartService::class);
        $cartService->addItem($product->id, 1);
        
        $cart = $cartService->getCart();
        $orderService = app(OrderService::class);
        
        $order = $orderService->createFromCart($cart, [
            'name' => 'Test User',
            'phone' => '08123456789',
            'address' => 'Test Address 123, City',
            'notes' => '',
        ], $user->id);

        // Silver tier has 5% discount
        $expectedSubtotal = $product->base_price;
        $expectedDiscount = $expectedSubtotal * 0.05;
        $expectedTotal = $expectedSubtotal - $expectedDiscount;

        $this->assertEquals(5, $order->discount_percent);
        $this->assertEquals($expectedDiscount, $order->discount_amount);
        $this->assertEquals($expectedTotal, $order->total_amount);
    }

    public function test_whatsapp_order_creates_payment_log(): void
    {
        $user = User::factory()->create();
        $product = Product::first();
        
        $this->actingAs($user);
        
        $cartService = app(CartService::class);
        $cartService->addItem($product->id, 1);
        
        $cart = $cartService->getCart();
        $orderService = app(OrderService::class);
        
        $result = $orderService->createWhatsAppOrder($cart, [
            'name' => 'Test User',
            'phone' => '08123456789',
            'address' => 'Test Address 123, City',
            'notes' => '',
        ], $user->id);

        $this->assertArrayHasKey('order', $result);
        $this->assertArrayHasKey('whatsapp_url', $result);
        $this->assertStringStartsWith('WA-', $result['order']->order_number);
        $this->assertEquals('pending_payment', $result['order']->status);
        
        // Check payment log was created
        $this->assertDatabaseHas('payment_logs', [
            'order_id' => $result['order']->id,
            'payment_method' => 'whatsapp',
            'status' => 'pending',
        ]);
    }

    public function test_points_are_awarded_on_order_completion(): void
    {
        $guestTier = \App\Models\LoyaltyTier::where('slug', 'guest')->first();
        $user = User::factory()->create([
            'loyalty_tier_id' => $guestTier->id,
            'points' => 0,
            'total_spend' => 0,
        ]);
        $product = Product::where('base_price', '>=', 100000)->first();
        
        $this->actingAs($user);
        
        $cartService = app(CartService::class);
        $cartService->addItem($product->id, 1);
        
        $cart = $cartService->getCart();
        $orderService = app(OrderService::class);
        
        $order = $orderService->createFromCart($cart, [
            'name' => 'Test User',
            'phone' => '08123456789',
            'address' => 'Test Address 123, City',
            'notes' => '',
        ], $user->id);

        $orderService->completeOrder($order);

        $user->refresh();
        
        // Points = floor(base_price / 10000) * multiplier (1.0 for guest)
        $expectedPoints = floor($product->base_price / 10000);
        
        $this->assertEquals($expectedPoints, $user->points);
        $this->assertEquals($product->base_price, $user->total_spend);
        
        // Check point transaction was recorded
        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $user->id,
            'order_id' => $order->id,
            'type' => 'earn',
            'amount' => $expectedPoints,
        ]);
    }

    public function test_user_is_upgraded_to_silver_after_spending_threshold(): void
    {
        $guestTier = \App\Models\LoyaltyTier::where('slug', 'guest')->first();
        $silverTier = \App\Models\LoyaltyTier::where('slug', 'silver')->first();
        
        $user = User::factory()->create([
            'loyalty_tier_id' => $guestTier->id,
            'points' => 0,
            'total_spend' => 4999000, // Just under 5 million
        ]);
        
        // Create a product that will push them over the threshold
        $product = Product::first();
        $product->update(['base_price' => 100000]); // 100k will push to 5.099 million
        
        $this->actingAs($user);
        
        $cartService = app(CartService::class);
        $cartService->addItem($product->id, 1);
        
        $cart = $cartService->getCart();
        $orderService = app(OrderService::class);
        
        $order = $orderService->createFromCart($cart, [
            'name' => 'Test User',
            'phone' => '08123456789',
            'address' => 'Test Address 123, City',
            'notes' => '',
        ], $user->id);

        $orderService->completeOrder($order);

        $user->refresh();
        
        $this->assertEquals($silverTier->id, $user->loyalty_tier_id);
    }
}
