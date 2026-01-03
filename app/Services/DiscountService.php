<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DiscountRule;
use App\Models\Order;
use App\Models\OrderDiscount;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * DiscountService
 * 
 * Handles complex discount calculations:
 * - Percentage discounts
 * - Fixed amount discounts
 * - Buy X Get Y promotions
 * - Bundle pricing
 * - Stackable vs non-stackable discounts
 * 
 * GOVERNANCE:
 * - All discount applications are logged to AuditEvent for traceability
 * - Idempotency enforced via idempotency_key on audit events
 */
class DiscountService
{
    public function __construct(
        protected readonly AuditEventService $auditEventService
    ) {}

    /**
     * Get all applicable discounts for a user's cart/order.
     */
    public function getApplicableDiscounts(
        User $user,
        Collection $items,
        float $orderTotal
    ): Collection {
        try {
            $discounts = DiscountRule::available()
                ->byPriority()
                ->get();
            
            return $discounts->filter(function ($discount) use ($user, $items, $orderTotal) {
                // Check if discount can be used by this user
                if (!$discount->canBeUsedBy($user)) {
                    return false;
                }
                
                // Check if any items match the discount targeting
                if ($discount->product_id || $discount->brand_id || $discount->category_id) {
                    $hasMatchingItem = $items->contains(function ($item) use ($discount) {
                        $product = $item['product'] ?? Product::find($item['product_id'] ?? null);
                        return $product && $discount->appliesTo($product, null, 0, $item['quantity'] ?? 1);
                    });
                    
                    if (!$hasMatchingItem) {
                        return false;
                    }
                }
                
                // Check minimum order amount
                if ($discount->min_order_amount && $orderTotal < $discount->min_order_amount) {
                    return false;
                }
                
                return true;
            });
        } catch (\Exception $e) {
            Log::error('DiscountService::getApplicableDiscounts failed', [
                'user_id' => $user->id ?? null,
                'order_total' => $orderTotal,
                'error' => $e->getMessage(),
            ]);
            
            return collect([]);
        }
    }

    /**
     * Calculate the best discount(s) for an order.
     * Returns the optimal combination considering stackability.
     */
    public function calculateBestDiscounts(
        User $user,
        Collection $items,
        float $orderTotal
    ): array {
        try {
            $applicableDiscounts = $this->getApplicableDiscounts($user, $items, $orderTotal);
            
            if ($applicableDiscounts->isEmpty()) {
                return [
                    'discounts' => [],
                    'total_discount' => 0,
                    'final_amount' => $orderTotal,
                ];
            }
            
            // Separate stackable and non-stackable discounts
            $stackable = $applicableDiscounts->where('is_stackable', true);
            $nonStackable = $applicableDiscounts->where('is_stackable', false);
            
            // Calculate total if using best non-stackable discount
            $bestNonStackable = null;
            $bestNonStackableAmount = 0;
            
            foreach ($nonStackable as $discount) {
                $amount = $this->calculateDiscountAmount($discount, $items, $orderTotal);
                if ($amount > $bestNonStackableAmount) {
                    $bestNonStackableAmount = $amount;
                    $bestNonStackable = $discount;
                }
            }
            
            // Calculate total if using all stackable discounts
            $stackableTotal = 0;
            $appliedStackable = [];
            $remainingAmount = $orderTotal;
            
            foreach ($stackable->sortByDesc('priority') as $discount) {
                $amount = $this->calculateDiscountAmount($discount, $items, $remainingAmount);
                if ($amount > 0) {
                    $stackableTotal += $amount;
                    $remainingAmount -= $amount;
                    $appliedStackable[] = [
                        'discount' => $discount,
                        'amount' => $amount,
                    ];
                }
            }
            
            // Determine best option
            if ($bestNonStackableAmount >= $stackableTotal) {
                return [
                    'discounts' => $bestNonStackable ? [[
                        'discount' => $bestNonStackable,
                        'amount' => $bestNonStackableAmount,
                    ]] : [],
                    'total_discount' => $bestNonStackableAmount,
                    'final_amount' => $orderTotal - $bestNonStackableAmount,
                ];
            }
            
            return [
                'discounts' => $appliedStackable,
                'total_discount' => $stackableTotal,
                'final_amount' => $orderTotal - $stackableTotal,
            ];
        } catch (\Exception $e) {
            Log::error('DiscountService::calculateBestDiscounts failed', [
                'user_id' => $user->id ?? null,
                'order_total' => $orderTotal,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'discounts' => [],
                'total_discount' => 0,
                'final_amount' => $orderTotal,
            ];
        }
    }

    /**
     * Calculate discount amount for a single rule.
     * @phpstan-ignore-next-line
     */
    public function calculateDiscountAmount(
        DiscountRule $discount,
        Collection $items,
        float $orderTotal
    ): float {
        $amount = 0;
        
        /** @phpstan-ignore-next-line */
        switch ($discount->discount_type) {
            case 'percentage':
                /** @phpstan-ignore-next-line */
                if ($discount->product_id || $discount->brand_id || $discount->category_id) {
                    // Apply to specific items
                    $amount = $this->calculateItemLevelDiscount($discount, $items);
                } else {
                    // Apply to order total
                    /** @phpstan-ignore-next-line */
                    $amount = $orderTotal * ((float) $discount->discount_value / 100);
                }
                break;
                
            case 'fixed_amount':
                /** @phpstan-ignore-next-line */
                $amount = (float) $discount->discount_value;
                break;
                
            case 'buy_x_get_y':
                $amount = $this->calculateBuyXGetYDiscount($discount, $items);
                break;
                
            case 'bundle_price':
                $amount = $this->calculateBundleDiscount($discount, $items, $orderTotal);
                break;
        }
        
        // Apply cap if set
        if ($discount->max_discount_amount && $amount > $discount->max_discount_amount) {
            $amount = $discount->max_discount_amount;
        }
        
        // Don't exceed order total
        $amount = min($amount, $orderTotal);
        
        return round($amount, 2);
    }

    /**
     * Calculate item-level percentage discount.
     */
    protected function calculateItemLevelDiscount(DiscountRule $discount, Collection $items): float
    {
        $totalDiscount = 0;
        
        foreach ($items as $item) {
            $product = $item['product'] ?? Product::find($item['product_id'] ?? null);
            $quantity = $item['quantity'] ?? 1;
            $unitPrice = $item['unit_price'] ?? $product?->base_price ?? 0;
            
            if ($product && $discount->appliesTo($product)) {
                $lineTotal = $unitPrice * $quantity;
                $totalDiscount += $lineTotal * ($discount->discount_value / 100);
            }
        }
        
        return $totalDiscount;
    }

    /**
     * Calculate Buy X Get Y discount.
     */
    protected function calculateBuyXGetYDiscount(DiscountRule $discount, Collection $items): float
    {
        $totalDiscount = 0;
        
        foreach ($items as $item) {
            $product = $item['product'] ?? Product::find($item['product_id'] ?? null);
            $quantity = $item['quantity'] ?? 1;
            $unitPrice = $item['unit_price'] ?? $product?->base_price ?? 0;
            
            if ($product && $discount->appliesTo($product)) {
                $buyQty = $discount->buy_quantity;
                $getQty = $discount->get_quantity;
                $getDiscountPercent = $discount->get_discount_percent ?? 100; // 100 = free
                
                // Calculate how many free/discounted items
                $totalPerSet = $buyQty + $getQty;
                $sets = floor($quantity / $totalPerSet);
                $freeItems = $sets * $getQty;
                
                $totalDiscount += $freeItems * $unitPrice * ($getDiscountPercent / 100);
            }
        }
        
        return $totalDiscount;
    }

    /**
     * Calculate bundle pricing discount.
     */
    protected function calculateBundleDiscount(
        DiscountRule $discount,
        Collection $items,
        float $orderTotal
    ): float {
        // Bundle price is a fixed price for the bundle
        // Discount is the difference between original total and bundle price
        return max(0, $orderTotal - $discount->discount_value);
    }

    /**
     * Apply calculated discounts to an order and save.
     * @phpstan-ignore-next-line
     */
    public function applyDiscountsToOrder(Order $order): Order
    {
        try {
            $user = $order->user;
            
            if (!$user) {
                return $order;
            }
            
            $items = $order->items->map(fn($item) => [
                'product' => $item->product,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
            ]);
            
            $orderTotal = (float) ($order->subtotal_before_tax ?? $order->subtotal ?? 0);
            $result = $this->calculateBestDiscounts($user, $items, $orderTotal);
            
            // Clear existing discounts
            $order->discounts()->delete();
            
            $discountBreakdown = [];
            
            // Create discount records
            foreach ($result['discounts'] as $discountData) {
                $discount = $discountData['discount'];
                $amount = $discountData['amount'];
                
                OrderDiscount::createFromRule(
                    $order,
                    $discount,
                    $orderTotal,
                    $amount,
                    null,
                    ['calculation_method' => $discount->discount_type]
                );
                
                // Increment usage
                $discount->incrementUsage();
                
                $discountBreakdown[] = [
                    'code' => $discount->code,
                    'name' => $discount->name,
                    'type' => $discount->discount_type,
                    'amount' => $amount,
                ];
            }
            
            // Update order totals
            /** @phpstan-ignore-next-line */
            $order->discount_amount = $result['total_discount'];
            /** @phpstan-ignore-next-line */
            $order->discount_breakdown = $discountBreakdown;
            $order->recalculateTotals();
            $order->save();
            
            // Governance: Log discount application for audit trail
            if (!empty($discountBreakdown)) {
                $this->auditEventService->record(
                    action: 'discount.applied',
                    entityType: Order::class,
                    entityId: $order->id,
                    meta: [
                        'order_number' => $order->order_number,
                        'user_id' => $user->id,
                        'discounts' => $discountBreakdown,
                        'total_discount' => $result['total_discount'],
                        'order_total_before' => $orderTotal,
                        'order_total_after' => $order->total_amount,
                    ],
                    idempotencyKey: "discount:order:{$order->id}",
                    requestId: request()?->attributes?->get('request_id'),
                );
            }
            
            return $order;
        } catch (\Exception $e) {
            Log::error('DiscountService::applyDiscountsToOrder failed', [
                'order_id' => $order->id ?? null,
                'error' => $e->getMessage(),
            ]);
            
            return $order;
        }
    }

    /**
     * Validate and apply a promo code.
     */
    public function applyPromoCode(Order $order, string $code): array
    {
        try {
            $discount = DiscountRule::where('code', $code)
                ->active()
                ->valid()
                ->first();
            
            if (!$discount) {
                return [
                    'success' => false,
                    'message' => 'Kode promo tidak ditemukan atau sudah tidak berlaku',
                ];
            }
            
            if (!$discount->canBeUsedBy($order->user)) {
                return [
                    'success' => false,
                    'message' => 'Kode promo tidak dapat digunakan',
                ];
            }
            
            /** @phpstan-ignore-next-line */
            $orderTotal = (float) ($order->subtotal_before_tax ?? $order->subtotal ?? 0);
            
            if ($discount->min_order_amount && $orderTotal < $discount->min_order_amount) {
                return [
                    'success' => false,
                    'message' => sprintf(
                        'Minimum order Rp %s untuk menggunakan kode ini',
                        number_format((float) $discount->min_order_amount, 0, ',', '.')
                    ),
                ];
            }
            
            $items = $order->items->map(fn($item) => [
                'product' => $item->product,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
            ]);
            
            $discountAmount = $this->calculateDiscountAmount($discount, $items, $orderTotal);
            
            if ($discountAmount <= 0) {
                return [
                    'success' => false,
                    'message' => 'Kode promo tidak berlaku untuk pesanan ini',
                ];
            }
            
            // Apply the discount
            OrderDiscount::createFromRule($order, $discount, $orderTotal, $discountAmount);
            $discount->incrementUsage();
            
            /** @phpstan-ignore-next-line */
            $order->discount_amount = (float) ($order->discount_amount ?? 0) + $discountAmount;
            /** @phpstan-ignore-next-line */
            $order->discount_breakdown = array_merge($order->discount_breakdown ?? [], [[
                'code' => $discount->code,
                'name' => $discount->name,
                'type' => $discount->discount_type,
                'amount' => $discountAmount,
            ]]);
            $order->recalculateTotals();
            $order->save();
            
            // Governance: Log promo code application for audit trail
            $this->auditEventService->record(
                action: 'discount.promo_code_applied',
                entityType: Order::class,
                entityId: $order->id,
                meta: [
                    'order_number' => $order->order_number,
                    'user_id' => $order->user_id,
                    'promo_code' => $code,
                    'discount_id' => $discount->id,
                    'discount_amount' => $discountAmount,
                    'order_total_before' => $orderTotal,
                    'order_total_after' => $order->total_amount,
                ],
                idempotencyKey: "promo:{$order->id}:{$code}",
                requestId: request()?->attributes?->get('request_id'),
            );
            
            return [
                'success' => true,
                'message' => sprintf(
                    'Kode promo berhasil diterapkan! Hemat Rp %s',
                    number_format($discountAmount, 0, ',', '.')
                ),
                'discount_amount' => $discountAmount,
            ];
        } catch (\Exception $e) {
            Log::error('DiscountService::applyPromoCode failed', [
                'order_id' => $order->id ?? null,
                'code' => $code,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat menerapkan kode promo',
            ];
        }
    }
}
