<?php

namespace Tests\Feature;

use App\Contracts\ReturnServiceInterface;
use App\Models\AuditEvent;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\ReturnItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ReturnGovernanceAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_loyalty_reversal_audit_event_is_idempotent(): void
    {
        Config::set('services.loyalty.reverse_on_returns', true);
        Config::set('services.loyalty.adjust_total_spend_on_returns', true);

        $user = User::factory()->create([
            'points' => 0,
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

        $idempotencyKey = "return:loyalty_reverse:{$return->id}";

        $this->assertSame(1, AuditEvent::where('idempotency_key', $idempotencyKey)->count());

        /** @var AuditEvent $event */
        $event = AuditEvent::where('idempotency_key', $idempotencyKey)->firstOrFail();

        $this->assertSame('order.return.loyalty_reversed', $event->action);
        $this->assertSame(OrderReturn::class, $event->entity_type);
        $this->assertSame($return->id, (int) $event->entity_id);

        $this->assertIsArray($event->meta);
        $this->assertSame($order->id, (int) ($event->meta['order_id'] ?? 0));
        $this->assertSame($user->id, (int) ($event->meta['user_id'] ?? 0));
        $this->assertSame(20000.0, (float) ($event->meta['refund_amount'] ?? 0));
        $this->assertSame(2, (int) ($event->meta['points_reversed'] ?? 0));
        $this->assertTrue((bool) ($event->meta['spend_adjusted'] ?? false));
        $this->assertSame(80000.0, (float) ($event->meta['new_total_spend'] ?? 0));
    }
}
