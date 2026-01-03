<?php

namespace Tests\Feature;

use App\Contracts\InventoryServiceInterface;
use App\Contracts\ReturnServiceInterface;
use App\Models\BatchInventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\ReturnItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ReturnInventoryIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_return_completion_restock_runs_once_under_retries(): void
    {
        $user = User::factory()->create();

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

        $batch = BatchInventory::create([
            'product_id' => $orderItem->product_id,
            'batch_number' => 'RET-BATCH-001',
            'lot_number' => 'RET-LOT-001',
            'quantity_received' => 10,
            'quantity_available' => 10,
            'quantity_sold' => 0,
            'quantity_damaged' => 0,
            'manufactured_at' => Carbon::now()->subMonths(6),
            'expires_at' => Carbon::now()->addMonths(6)->toDateString(),
            'received_at' => Carbon::now()->subMonths(5),
            'cost_price' => 10000,
            'is_active' => true,
            'is_near_expiry' => false,
            'is_expired' => false,
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
            'batch_inventory_id' => $batch->id,
            'quantity_requested' => 1,
            'quantity_received' => 1,
            'quantity_approved' => 1,
            'unit_price' => 20000,
            'line_total' => 20000,
            'restock' => true,
        ]);

        $expectedAllocations = [[
            'batch_id' => $batch->id,
            'quantity' => 1,
            'product_id' => $orderItem->product_id,
        ]];

        $mock = Mockery::mock(InventoryServiceInterface::class);
        $mock->shouldReceive('releaseStock')
            ->once()
            ->with($expectedAllocations, "return:{$return->id}")
            ->andReturnNull();

        app()->instance(InventoryServiceInterface::class, $mock);

        $service = app(ReturnServiceInterface::class);
        $service->completeReturn($return, processedBy: null, requestId: 'test-req-1');
        $service->completeReturn($return, processedBy: null, requestId: 'test-req-2');

        $return->refresh();
        $this->assertNotNull($return->inventory_restocked_at);
    }
}
