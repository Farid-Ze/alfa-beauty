<?php

namespace Tests\Feature;

use App\Contracts\ReturnServiceInterface;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\PointTransaction;
use App\Models\ReturnItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ReturnLoyaltyReversalTest extends TestCase
{
    use RefreshDatabase;

    public function test_return_completion_reverses_loyalty_once_when_enabled(): void
    {
        Config::set('services.loyalty.reverse_on_returns', true);
        Config::set('services.loyalty.adjust_total_spend_on_returns', true);

        $user = User::factory()->create([
            'points' => 50,
            'total_spend' => 100000,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'total_amount' => 100000,
        ]);

        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'quantity' => 1,
            'unit_price' => 20000,
            'total_price' => 20000,
            'unit_price_before_tax' => 20000,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'subtotal_before_tax' => 20000,
        ]);

        $return = OrderReturn::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'status' => 'approved',
            'return_type' => 'refund',
            'reason_code' => 'defective',
            'refund_status' => 'pending',
            'restocking_fee' => 0,
        ]);

        ReturnItem::create([
            'order_return_id' => $return->id,
            'order_item_id' => $orderItem->id,
            'product_id' => $orderItem->product_id,
            'batch_inventory_id' => null,
            'quantity_requested' => 1,
            'quantity_received' => 1,
            'quantity_approved' => 1,
            'unit_price' => 20000,
            'line_total' => 20000,
            'restock' => false,
        ]);

        $service = app(ReturnServiceInterface::class);

        $service->completeReturn($return, processedBy: null, requestId: 'test-req-1');
        $service->completeReturn($return, processedBy: null, requestId: 'test-req-2');

        $return->refresh();
        $user->refresh();

        $this->assertSame('completed', $return->status);
        $this->assertNotNull($return->loyalty_reversed_at);

        $txIdempotencyKey = "reverse:return:{$return->id}:order:{$order->id}:user:{$user->id}";

        $this->assertEquals(1, PointTransaction::where('idempotency_key', $txIdempotencyKey)->count());
        $this->assertDatabaseHas('point_transactions', [
            'idempotency_key' => $txIdempotencyKey,
            'user_id' => $user->id,
            'order_id' => $order->id,
            'type' => 'adjust',
            'amount' => -2,
        ]);

        $this->assertSame(48, (int) $user->points);
        $this->assertEquals(80000, (int) $user->total_spend);
    }
}
