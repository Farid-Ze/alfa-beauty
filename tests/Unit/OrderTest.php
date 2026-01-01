<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
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

    protected function createOrder(User $user): Order
    {
        return Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-TEST-' . uniqid(),
            'total_amount' => 500000,
            'shipping_cost' => 25000,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'shipping_address' => 'Test Address',
        ]);
    }

    public function test_order_belongs_to_user(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $order = $this->createOrder($user);

        $this->assertInstanceOf(User::class, $order->user);
        $this->assertEquals($user->id, $order->user->id);
    }

    public function test_order_has_items(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test2@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $order = $this->createOrder($user);
        $product = Product::first();

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 250000,
            'total_price' => 500000,
        ]);

        $this->assertCount(1, $order->items);
        $this->assertEquals(2, $order->items->first()->quantity);
    }

    public function test_order_requires_user_id(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test3@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $order = $this->createOrder($user);

        $this->assertNotNull($order->user_id);
        $this->assertEquals($user->id, $order->user_id);
    }

    public function test_order_has_order_number(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test4@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $order = $this->createOrder($user);

        $this->assertNotNull($order->order_number);
        $this->assertStringStartsWith('ORD-', $order->order_number);
    }

    public function test_order_total_amount_cast_to_float(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test5@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-TEST-789',
            'total_amount' => '525000.50',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'shipping_address' => 'Test',
        ]);

        $this->assertIsFloat($order->total_amount);
        $this->assertEquals(525000.50, $order->total_amount);
    }

    public function test_order_number_is_unique(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test6@example.com',
            'password' => 'password',
            'loyalty_tier_id' => 1,
        ]);

        Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-UNIQUE-001',
            'total_amount' => 100000,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'shipping_address' => 'Test',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-UNIQUE-001', // Duplicate
            'total_amount' => 200000,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'shipping_address' => 'Test 2',
        ]);
    }
}
