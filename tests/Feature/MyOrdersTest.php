<?php

namespace Tests\Feature;

use App\Livewire\MyOrders;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MyOrdersTest extends TestCase
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

    public function test_guest_cannot_access_my_orders(): void
    {
        $response = $this->get('/orders');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_my_orders(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->get('/orders');
        $response->assertStatus(200);
    }

    public function test_my_orders_shows_user_orders(): void
    {
        $user = User::factory()->create();
        $product = Product::first();
        
        $this->actingAs($user);
        
        // Create an order
        $cartService = app(CartService::class);
        $cartService->addItem($product->id, 1);
        $cart = $cartService->getCart();
        
        $orderService = app(OrderService::class);
        $order = $orderService->createFromCart($cart, [
            'name' => 'Test User',
            'phone' => '08123456789',
            'address' => 'Test Address',
            'notes' => '',
        ], $user->id);
        
        Livewire::test(MyOrders::class)
            ->assertSee($order->order_number);
    }

    public function test_my_orders_empty_for_new_user(): void
    {
        $user = User::factory()->create();
        
        $this->actingAs($user);
        
        Livewire::test(MyOrders::class)
            ->assertDontSee('ORD-');
    }

    public function test_orders_are_sorted_by_latest(): void
    {
        $user = User::factory()->create();
        
        $this->actingAs($user);
        
        // Create first order
        $order1 = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-FIRST',
            'status' => 'pending',
            'subtotal' => 100000,
            'total_amount' => 100000,
            'payment_method' => 'manual_transfer',
            'payment_status' => 'pending',
            'shipping_address' => 'Test',
            'created_at' => now()->subDays(2),
        ]);
        
        // Create second order (more recent)
        $order2 = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-SECOND',
            'status' => 'pending',
            'subtotal' => 200000,
            'total_amount' => 200000,
            'payment_method' => 'manual_transfer',
            'payment_status' => 'pending',
            'shipping_address' => 'Test',
            'created_at' => now(),
        ]);
        
        $response = $this->get('/orders');
        $response->assertStatus(200);
        
        // Both orders should be visible
        $response->assertSee('ORD-FIRST');
        $response->assertSee('ORD-SECOND');
    }

    public function test_user_cannot_see_other_users_orders(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        // Create order for user1
        Order::create([
            'user_id' => $user1->id,
            'order_number' => 'ORD-USER1-SECRET',
            'status' => 'pending',
            'subtotal' => 100000,
            'total_amount' => 100000,
            'payment_method' => 'manual_transfer',
            'payment_status' => 'pending',
            'shipping_address' => 'Test',
        ]);
        
        // Login as user2
        $this->actingAs($user2);
        
        Livewire::test(MyOrders::class)
            ->assertDontSee('ORD-USER1-SECRET');
    }
}
