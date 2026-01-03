<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\InventoryServiceInterface;
use App\Contracts\ReturnServiceInterface;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\PointTransaction;
use App\Models\ReturnItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReturnService implements ReturnServiceInterface
{
    public function __construct(
        protected InventoryServiceInterface $inventoryService,
        protected AuditEventService $auditEventService,
    ) {
    }

    public function approveReturn(OrderReturn $return, ?int $processedBy = null, ?string $requestId = null): OrderReturn
    {
        return DB::transaction(function () use ($return, $processedBy, $requestId) {
            /** @var OrderReturn $locked */
            $locked = OrderReturn::whereKey($return->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === 'approved' && $locked->approved_at) {
                return $locked;
            }

            if ($locked->status !== 'requested') {
                throw new \RuntimeException('Return cannot be approved');
            }

            $locked->update([
                'status' => 'approved',
                'processed_by' => $processedBy,
                'approved_at' => now(),
            ]);

            $this->auditEventService->record(
                action: 'order.return.approved',
                entityType: OrderReturn::class,
                entityId: $locked->id,
                meta: [
                    'order_id' => $locked->order_id,
                    'user_id' => $locked->user_id,
                    'return_type' => $locked->return_type,
                    'status' => $locked->status,
                ],
                idempotencyKey: "return:approve:{$locked->id}",
                requestId: $requestId,
                actorUserId: $processedBy,
            );

            return $locked->fresh(['items']);
        });
    }

    public function markReturnReceived(OrderReturn $return, ?int $processedBy = null, ?string $requestId = null): OrderReturn
    {
        return DB::transaction(function () use ($return, $processedBy, $requestId) {
            /** @var OrderReturn $locked */
            $locked = OrderReturn::whereKey($return->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === 'received' && $locked->received_at) {
                return $locked;
            }

            if (!in_array($locked->status, ['approved', 'received'], true)) {
                throw new \RuntimeException('Return cannot be marked received');
            }

            $locked->update([
                'status' => 'received',
                'processed_by' => $processedBy ?? $locked->processed_by,
                'received_at' => $locked->received_at ?: now(),
            ]);

            $this->auditEventService->record(
                action: 'order.return.received',
                entityType: OrderReturn::class,
                entityId: $locked->id,
                meta: [
                    'order_id' => $locked->order_id,
                    'user_id' => $locked->user_id,
                    'return_type' => $locked->return_type,
                    'status' => $locked->status,
                ],
                idempotencyKey: "return:received:{$locked->id}",
                requestId: $requestId,
                actorUserId: $processedBy,
            );

            return $locked->fresh(['items']);
        });
    }

    public function completeReturn(OrderReturn $return, ?int $processedBy = null, ?string $requestId = null): OrderReturn
    {
        return DB::transaction(function () use ($return, $processedBy, $requestId) {
            /** @var OrderReturn $locked */
            $locked = OrderReturn::whereKey($return->id)
                ->lockForUpdate()
                ->with(['order', 'user', 'items', 'items.batchInventory', 'items.product'])
                ->firstOrFail();

            // Idempotency: if already completed, do not re-run side effects.
            if ($locked->status === 'completed' && $locked->completed_at) {
                return $locked;
            }

            if (in_array($locked->status, ['rejected'], true)) {
                throw new \RuntimeException('Rejected return cannot be completed');
            }

            if (!in_array($locked->status, ['approved', 'received', 'inspected', 'completed'], true)) {
                throw new \RuntimeException('Return cannot be completed');
            }

            // Normalize item totals based on approved quantities.
            foreach ($locked->items as $item) {
                /** @var ReturnItem $item */
                $approved = (int) ($item->quantity_approved ?? 0);
                if ($approved <= 0) {
                    if ((float) ($item->line_total ?? 0) !== 0.0) {
                        $item->update(['line_total' => 0]);
                    }
                    continue;
                }

                $expectedLineTotal = $approved * (float) ($item->unit_price ?? 0);
                if ((float) ($item->line_total ?? 0) !== (float) $expectedLineTotal) {
                    $item->update(['line_total' => $expectedLineTotal]);
                }
            }

            // Restock inventory once-only if requested.
            if ($this->orderReturnsHasColumn($locked, 'inventory_restocked_at') && !$locked->inventory_restocked_at) {
                $allocations = [];

                foreach ($locked->items as $item) {
                    /** @var ReturnItem $item */
                    $approved = (int) ($item->quantity_approved ?? 0);
                    if (!$item->restock || $approved <= 0) {
                        continue;
                    }

                    $allocations[] = [
                        'batch_id' => $item->batch_inventory_id,
                        'quantity' => $approved,
                        'product_id' => $item->product_id,
                    ];
                }

                if (!empty($allocations)) {
                    $this->inventoryService->releaseStock($allocations, "return:{$locked->id}");
                }

                $locked->forceFill(['inventory_restocked_at' => now()])->save();
            }

            // Recalculate refund amounts and complete.
            $locked->refresh();
            $locked->load(['items']);
            $locked->calculateRefundAmount();

            // Optional: reverse points/total_spend for refunds (business rule toggle).
            $loyaltyReversed = false;
            if ((bool) config('services.loyalty.reverse_on_returns', false)) {
                $loyaltyReversed = $this->reverseLoyaltyForRefundReturn($locked, (float) $locked->refund_amount, $processedBy, $requestId);
            }

            $locked->update([
                'status' => 'completed',
                'processed_by' => $processedBy ?? $locked->processed_by,
                'refund_status' => 'completed',
                'completed_at' => now(),
                'return_value' => $locked->return_value,
                'refund_amount' => $locked->refund_amount,
            ]);

            if ($loyaltyReversed && $this->orderReturnsHasColumn($locked, 'loyalty_reversed_at') && !$locked->loyalty_reversed_at) {
                $locked->forceFill(['loyalty_reversed_at' => now()])->save();
            }

            $this->auditEventService->record(
                action: 'order.return.completed',
                entityType: OrderReturn::class,
                entityId: $locked->id,
                meta: [
                    'order_id' => $locked->order_id,
                    'user_id' => $locked->user_id,
                    'return_type' => $locked->return_type,
                    'return_value' => $locked->return_value,
                    'restocking_fee' => $locked->restocking_fee,
                    'refund_amount' => $locked->refund_amount,
                    'refund_status' => $locked->refund_status,
                ],
                idempotencyKey: "return:complete:{$locked->id}",
                requestId: $requestId,
                actorUserId: $processedBy,
            );

            return $locked->fresh(['items']);
        });
    }

    public function rejectReturn(OrderReturn $return, string $reason, ?int $processedBy = null, ?string $requestId = null): OrderReturn
    {
        return DB::transaction(function () use ($return, $reason, $processedBy, $requestId) {
            /** @var OrderReturn $locked */
            $locked = OrderReturn::whereKey($return->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === 'rejected') {
                return $locked;
            }

            if ($locked->status === 'completed') {
                throw new \RuntimeException('Completed return cannot be rejected');
            }

            $locked->update([
                'status' => 'rejected',
                'processed_by' => $processedBy ?? $locked->processed_by,
                'reason_notes' => $reason,
                'refund_status' => 'declined',
            ]);

            $this->auditEventService->record(
                action: 'order.return.rejected',
                entityType: OrderReturn::class,
                entityId: $locked->id,
                meta: [
                    'order_id' => $locked->order_id,
                    'user_id' => $locked->user_id,
                    'return_type' => $locked->return_type,
                    'reason' => $reason,
                    'status' => $locked->status,
                ],
                idempotencyKey: "return:reject:{$locked->id}",
                requestId: $requestId,
                actorUserId: $processedBy,
            );

            return $locked;
        });
    }

    protected function orderReturnsHasColumn(OrderReturn $return, string $column): bool
    {
        static $cache = [];
        $key = "order_returns.{$column}";

        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        $cache[$key] = \Illuminate\Support\Facades\Schema::hasColumn('order_returns', $column);
        return $cache[$key];
    }

    protected function reverseLoyaltyForRefundReturn(OrderReturn $return, float $refundAmount, ?int $processedBy, ?string $requestId): bool
    {
        if ($refundAmount <= 0) {
            return false;
        }

        if (!$this->orderReturnsHasColumn($return, 'loyalty_reversed_at')) {
            return false;
        }

        if ($return->loyalty_reversed_at) {
            return true;
        }

        if ($return->return_type !== 'refund') {
            return false;
        }

        /** @var Order|null $order */
        $order = $return->relationLoaded('order') ? $return->order : Order::find($return->order_id);
        if (!$order || !$order->user_id) {
            return false;
        }

        /** @var User|null $user */
        $user = User::whereKey($order->user_id)->lockForUpdate()->first();
        if (!$user) {
            return false;
        }

        // Prefer proportional reversal based on original earned points for the order.
        $earnedPoints = 0;
        if ($this->pointTransactionsHasColumn('idempotency_key')) {
            $txIdempotencyKey = "earn:order:{$order->id}:user:{$user->id}";
            $earnedTx = PointTransaction::where('idempotency_key', $txIdempotencyKey)->first();
        } else {
            $earnedTx = PointTransaction::where('order_id', $order->id)->where('type', 'earn')->orderBy('id', 'asc')->first();
        }
        if (isset($earnedTx) && $earnedTx) {
            $earnedPoints = (int) abs((int) $earnedTx->amount);
        }

        $pointsToReverse = 0;
        if ($earnedPoints > 0 && (float) $order->total_amount > 0) {
            $ratio = min(1.0, max(0.0, $refundAmount / (float) $order->total_amount));
            $pointsToReverse = (int) floor($earnedPoints * $ratio);
        }

        if ($pointsToReverse <= 0) {
            // Fallback: compute from refund amount using current multiplier.
            $pointsToReverse = (int) max(0, $user->calculatePointsForPurchase($refundAmount));
        }

        // Idempotent points reversal transaction (reuses existing user-level idempotency patterns).
        if ($pointsToReverse > 0) {
            $txIdempotencyKey = "reverse:return:{$return->id}:order:{$order->id}:user:{$user->id}";
            $description = $return->return_number
                ? "Refund points reversal for return {$return->return_number}"
                : "Refund points reversal for return #{$return->id}";

            $user->addPoints(
                points: -$pointsToReverse,
                type: 'adjust',
                orderId: $order->id,
                description: $description,
                idempotencyKey: $txIdempotencyKey,
                requestId: $requestId,
            );
        }

        $spendAdjusted = false;
        $newSpend = (float) $user->total_spend;
        if ((bool) config('services.loyalty.adjust_total_spend_on_returns', false)) {
            // Adjust total_spend (policy toggle; tier downgrade is intentionally not applied).
            $newSpend = max(0.0, (float) $user->total_spend - $refundAmount);
            $user->forceFill(['total_spend' => $newSpend])->save();
            $spendAdjusted = true;
        }

        $this->auditEventService->record(
            action: 'order.return.loyalty_reversed',
            entityType: OrderReturn::class,
            entityId: $return->id,
            meta: [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'refund_amount' => $refundAmount,
                'points_reversed' => $pointsToReverse,
                'spend_adjusted' => $spendAdjusted,
                'new_total_spend' => $newSpend,
            ],
            idempotencyKey: "return:loyalty_reverse:{$return->id}",
            requestId: $requestId,
            actorUserId: $processedBy,
        );

        return true;
    }

    protected function pointTransactionsHasColumn(string $column): bool
    {
        static $cache = [];
        $key = "point_transactions.{$column}";
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }
        $cache[$key] = Schema::hasColumn('point_transactions', $column);
        return $cache[$key];
    }
}
