<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderCancellation;
use App\Models\PaymentLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCancellationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\LoyaltyTierSeeder::class);
        $this->seed(\Database\Seeders\BrandSeeder::class);
        $this->seed(\Database\Seeders\CategorySeeder::class);
    }

    public function test_pending_order_can_be_cancelled(): void
    {
        $user = User::factory()->create();
        
        $order = Order::factory()
            ->for($user)
            ->pending()
            ->create();

        $this->assertTrue($order->canBeCancelled());
        
        $cancellation = $order->cancel('customer_request', 'Changed my mind', $user->id);

        $this->assertInstanceOf(OrderCancellation::class, $cancellation);
        
        $order->refresh();
        $this->assertEquals(Order::STATUS_CANCELLED, $order->status);
    }

    public function test_pending_payment_order_can_be_cancelled(): void
    {
        $user = User::factory()->create();
        
        $order = Order::factory()
            ->for($user)
            ->create([
                'status' => Order::STATUS_PENDING_PAYMENT,
                'payment_status' => Order::PAYMENT_PENDING,
            ]);

        $this->assertTrue($order->canBeCancelled());
        
        $order->cancel('payment_timeout', 'Payment not received');

        $order->refresh();
        $this->assertEquals(Order::STATUS_CANCELLED, $order->status);
    }

    public function test_processing_order_cannot_be_cancelled(): void
    {
        $user = User::factory()->create();
        
        $order = Order::factory()
            ->for($user)
            ->processing()
            ->create();

        $this->assertFalse($order->canBeCancelled());
    }

    public function test_shipped_order_cannot_be_cancelled(): void
    {
        $user = User::factory()->create();
        
        $order = Order::factory()
            ->for($user)
            ->create([
                'status' => Order::STATUS_SHIPPED,
            ]);

        $this->assertFalse($order->canBeCancelled());
    }

    public function test_delivered_order_cannot_be_cancelled(): void
    {
        $user = User::factory()->create();
        
        $order = Order::factory()
            ->for($user)
            ->delivered()
            ->create();

        $this->assertFalse($order->canBeCancelled());
    }

    public function test_cancellation_records_reason_and_notes(): void
    {
        $user = User::factory()->create();
        
        $order = Order::factory()
            ->for($user)
            ->pending()
            ->create();

        $cancellation = $order->cancel(
            'out_of_stock',
            'Product no longer available',
            $user->id
        );

        $this->assertEquals('out_of_stock', $cancellation->reason_code);
        $this->assertEquals('Product no longer available', $cancellation->reason_notes);
        $this->assertEquals($user->id, $cancellation->cancelled_by);
    }

    public function test_cancellation_with_payment_creates_pending_refund(): void
    {
        $user = User::factory()->create();
        
        $order = Order::factory()
            ->for($user)
            ->pending()
            ->create([
                'amount_paid' => 500000,
                'balance_due' => 0,
            ]);

        $cancellation = $order->cancel('customer_request');

        $this->assertEquals(500000, $cancellation->refund_amount);
        $this->assertEquals('pending', $cancellation->refund_status);
    }

    public function test_cancellation_without_payment_has_completed_refund(): void
    {
        $user = User::factory()->create();
        
        $order = Order::factory()
            ->for($user)
            ->pending()
            ->create([
                'amount_paid' => 0,
                'balance_due' => 500000,
            ]);

        $cancellation = $order->cancel('customer_request');

        $this->assertEquals(0, $cancellation->refund_amount);
        $this->assertEquals('completed', $cancellation->refund_status);
    }

    public function test_already_cancelled_order_cannot_be_cancelled_again(): void
    {
        $user = User::factory()->create();
        
        $order = Order::factory()
            ->for($user)
            ->cancelled()
            ->create();

        $this->assertFalse($order->canBeCancelled());
    }

    public function test_cancel_throws_exception_for_non_cancellable_order(): void
    {
        $user = User::factory()->create();
        
        $order = Order::factory()
            ->for($user)
            ->processing()
            ->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Order cannot be cancelled');

        $order->cancel('customer_request');
    }
}
