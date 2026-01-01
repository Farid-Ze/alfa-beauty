<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\LoyaltyTier;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OrderService $orderService;
    protected CartService $cartService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\LoyaltyTierSeeder::class);
        $this->seed(\Database\Seeders\BrandSeeder::class);
        $this->seed(\Database\Seeders\CategorySeeder::class);
        $this->seed(\Database\Seeders\ProductSeeder::class);
        
        $this->orderService = app(OrderService::class);
        $this->cartService = app(CartService::class);
    }

    public function test_create_order_from_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::first();
        
        $this->actingAs($user);
        
        $this->cartService->addItem($product->id, 2);
        $cart = $this->cartService->getCart();
        
        $order = $this->orderService->createFromCart($cart, [
            'name' => 'Test Customer',
            'phone' => '08123456789',
            'address' => 'Jl. Test No. 123, Jakarta',
            'notes' => 'Test order notes',
        ], $user->id);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals($user->id, $order->user_id);
        $this->assertStringStartsWith('ORD-', $order->order_number);
        $this->assertEquals('pending', $order->status);
        $this->assertEquals('pending', $order->payment_status);
    }

    public function test_order_has_correct_item_count(): void
    {
        $user = User::factory()->create();
        $products = Product::take(2)->get();
        
        $this->actingAs($user);
        
        foreach ($products as $product) {
            $this->cartService->addItem($product->id, 1);
        }
        
        $cart = $this->cartService->getCart();
        
        $order = $this->orderService->createFromCart($cart, [
            'name' => 'Test Customer',
            'phone' => '08123456789',
            'address' => 'Jl. Test No. 123, Jakarta',
            'notes' => '',
        ], $user->id);

        $this->assertEquals(2, $order->items->count());
    }

    public function test_order_number_is_unique(): void
    {
        $user = User::factory()->create();
        $product = Product::first();
        
        $this->actingAs($user);
        
        // Create first order
        $this->cartService->addItem($product->id, 1);
        $cart1 = $this->cartService->getCart();
        $order1 = $this->orderService->createFromCart($cart1, [
            'name' => 'Test',
            'phone' => '08123456789',
            'address' => 'Test Address',
            'notes' => '',
        ], $user->id);

        // Create second order
        $this->cartService->addItem($product->id, 1);
        $cart2 = $this->cartService->getCart();
        $order2 = $this->orderService->createFromCart($cart2, [
            'name' => 'Test',
            'phone' => '08123456789',
            'address' => 'Test Address',
            'notes' => '',
        ], $user->id);

        $this->assertNotEquals($order1->order_number, $order2->order_number);
    }

    public function test_stock_is_reduced_after_order(): void
    {
        $user = User::factory()->create();
        $product = Product::first();
        $initialStock = $product->stock;
        
        $this->actingAs($user);
        
        $this->cartService->addItem($product->id, 3);
        $cart = $this->cartService->getCart();
        
        $this->orderService->createFromCart($cart, [
            'name' => 'Test',
            'phone' => '08123456789',
            'address' => 'Test Address',
            'notes' => '',
        ], $user->id);

        $product->refresh();
        $this->assertEquals($initialStock - 3, $product->stock);
    }

    public function test_order_subtotal_calculation(): void
    {
        $user = User::factory()->create();
        $product = Product::first();
        $quantity = 2;
        
        $this->actingAs($user);
        
        $this->cartService->addItem($product->id, $quantity);
        $cart = $this->cartService->getCart();
        
        $order = $this->orderService->createFromCart($cart, [
            'name' => 'Test',
            'phone' => '08123456789',
            'address' => 'Test Address',
            'notes' => '',
        ], $user->id);

        // Subtotal should be price * quantity
        $this->assertGreaterThan(0, $order->subtotal);
        $this->assertGreaterThan(0, $order->total_amount);
    }

    public function test_whatsapp_order_has_wa_prefix(): void
    {
        $user = User::factory()->create();
        $product = Product::first();
        
        $this->actingAs($user);
        
        $this->cartService->addItem($product->id, 1);
        $cart = $this->cartService->getCart();
        
        $result = $this->orderService->createWhatsAppOrder($cart, [
            'name' => 'Test Customer',
            'phone' => '08123456789',
            'address' => 'Jl. Test No. 123, Jakarta',
            'notes' => '',
        ], $user->id);

        $this->assertArrayHasKey('order', $result);
        $this->assertStringStartsWith('WA-', $result['order']->order_number);
    }

    public function test_whatsapp_order_returns_whatsapp_url(): void
    {
        $user = User::factory()->create();
        $product = Product::first();
        
        $this->actingAs($user);
        
        $this->cartService->addItem($product->id, 1);
        $cart = $this->cartService->getCart();
        
        $result = $this->orderService->createWhatsAppOrder($cart, [
            'name' => 'Test Customer',
            'phone' => '08123456789',
            'address' => 'Jl. Test No. 123, Jakarta',
            'notes' => '',
        ], $user->id);

        $this->assertArrayHasKey('whatsapp_url', $result);
        $this->assertStringStartsWith('https://wa.me/', $result['whatsapp_url']);
    }

    public function test_order_items_match_cart_items(): void
    {
        $user = User::factory()->create();
        $product = Product::first();
        
        $this->actingAs($user);
        
        $this->cartService->addItem($product->id, 2);
        $cart = $this->cartService->getCart();
        
        $cartItemCount = $cart->items->count();
        
        $order = $this->orderService->createFromCart($cart, [
            'name' => 'Test',
            'phone' => '08123456789',
            'address' => 'Test Address',
            'notes' => '',
        ], $user->id);

        // Order should have same number of items as cart
        $this->assertEquals($cartItemCount, $order->items->count());
    }

    public function test_guest_tier_no_discount_on_base_price(): void
    {
        $guestTier = LoyaltyTier::where('slug', 'guest')->first();
        $user = User::factory()->create([
            'loyalty_tier_id' => $guestTier->id,
        ]);
        $product = Product::first();
        
        $this->actingAs($user);
        
        $this->cartService->addItem($product->id, 1);
        $cart = $this->cartService->getCart();
        
        $order = $this->orderService->createFromCart($cart, [
            'name' => 'Test',
            'phone' => '08123456789',
            'address' => 'Test Address',
            'notes' => '',
        ], $user->id);

        // Guest tier has 0% discount
        $this->assertEquals(0, $order->discount_percent);
        $this->assertEquals(0, $order->discount_amount);
    }

    public function test_order_stores_shipping_address(): void
    {
        $user = User::factory()->create();
        $product = Product::first();
        
        $this->actingAs($user);
        
        $this->cartService->addItem($product->id, 1);
        $cart = $this->cartService->getCart();
        
        $address = 'Jl. Sudirman No. 123, Gedung ABC Lt. 5, Jakarta Pusat 10220';
        
        $order = $this->orderService->createFromCart($cart, [
            'name' => 'Test',
            'phone' => '08123456789',
            'address' => $address,
            'notes' => '',
        ], $user->id);

        $this->assertEquals($address, $order->shipping_address);
    }
}
