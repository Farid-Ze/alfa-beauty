<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\LoyaltyTier;
use App\Models\Order;
use App\Models\PaymentLog;
use App\Models\User;
use App\Notifications\OrderConfirmation;
use App\Notifications\PaymentReceived;
use App\Notifications\TierUpgraded;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    protected InventoryService $inventoryService;
    protected PricingService $pricingService;

    public function __construct(InventoryService $inventoryService, PricingService $pricingService)
    {
        $this->inventoryService = $inventoryService;
        $this->pricingService = $pricingService;
    }

    /**
     * Create a new order from a cart instance.
     * 
     * Uses B2B pricing via PricingService for customer-specific prices.
     *
     * @param Cart $cart
     * @param array $customerDetails ['name', 'phone', 'address', 'notes']
     * @param int|null $userId
     * @return Order
     * @throws \Exception
     */
    public function createFromCart(Cart $cart, array $customerDetails, ?int $userId = null): Order
    {
        return DB::transaction(function () use ($cart, $customerDetails, $userId) {
            $user = $userId ? User::find($userId) : null;
            
            // Get B2B prices for all cart items in bulk (single query)
            $productQuantities = $cart->items->pluck('quantity', 'product_id')->toArray();
            $products = \App\Models\Product::whereIn('id', array_keys($productQuantities))->get();
            
            $productsWithQty = $products->map(function ($product) use ($productQuantities) {
                $product->quantity = $productQuantities[$product->id] ?? 1;
                return $product;
            });
            
            $prices = $this->pricingService->getBulkPrices($productsWithQty, $user);
            $productsById = $products->keyBy('id');

            // Calculate subtotal using B2B prices
            $subtotal = 0;
            $lineItems = [];
            
            foreach ($cart->items as $item) {
                $priceInfo = $prices[$item->product_id] ?? ['price' => $item->product->base_price];
                $unitPrice = $priceInfo['price'];
                $lineTotal = $item->quantity * $unitPrice;
                $subtotal += $lineTotal;
                
                $lineItems[$item->product_id] = [
                    'quantity' => $item->quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'price_source' => $priceInfo['source'] ?? 'base_price',
                    'original_price' => $priceInfo['original_price'] ?? $unitPrice,
                ];
            }

            // B2B pricing already includes discounts from customer price lists
            // Only apply additional tier discount if not already using customer-specific pricing
            $discountPercent = 0;
            $discountAmount = 0;
            
            // Check if any item used customer-specific pricing
            $hasCustomerPricing = collect($lineItems)->contains(function ($item) {
                return in_array($item['price_source'] ?? '', ['customer_price_list', 'volume_tier']);
            });
            
            // If using base prices with loyalty tier, apply tier discount
            if (!$hasCustomerPricing && $user?->loyaltyTier) {
                $discountPercent = $user->loyaltyTier->discount_percent ?? 0;
                if ($discountPercent > 0) {
                    $discountAmount = $subtotal * ($discountPercent / 100);
                }
            }
            
            $totalAmount = $subtotal - $discountAmount;

            // Create Order
            $order = Order::create([
                'user_id' => $userId,
                'order_number' => 'ORD-' . strtoupper(uniqid()),
                'status' => 'pending',
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tier_discount_percent' => $discountPercent,
                'total_amount' => $totalAmount,
                'payment_method' => 'manual_transfer',
                'payment_status' => 'pending',
                'shipping_address' => $customerDetails['address'],
                'shipping_method' => 'standard', 
                'shipping_cost' => 0,
                'notes' => $this->formatNotes($customerDetails),
            ]);

            // Create Order Items with FEFO batch allocation
            foreach ($cart->items as $item) {
                $lineItem = $lineItems[$item->product_id];
                
                // Allocate stock from batches using FEFO algorithm
                try {
                    $allocations = $this->inventoryService->allocateStock(
                        $item->product_id,
                        $item->quantity,
                        $order->order_number
                    );
                } catch (\Exception $e) {
                    Log::error('FEFO allocation failed', [
                        'order' => $order->order_number,
                        'product_id' => $item->product_id,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }

                $order->items()->create([
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $lineItem['unit_price'],
                    'total_price' => $lineItem['line_total'],
                    'batch_allocations' => $allocations, // Store FEFO allocations for BPOM
                ]);
            }

            // Send order confirmation notification (queued)
            if ($user) {
                $user->notify(new OrderConfirmation($order));
            }

            return $order;
        });
    }

    /**
     * Create order for WhatsApp checkout flow.
     * 
     * Creates order with pending_payment status and logs the WhatsApp payment intent.
     * Uses B2B pricing via PricingService.
     *
     * @param Cart $cart
     * @param array $customerDetails
     * @param int|null $userId
     * @return array ['order' => Order, 'whatsapp_url' => string]
     */
    public function createWhatsAppOrder(Cart $cart, array $customerDetails, ?int $userId = null): array
    {
        return DB::transaction(function () use ($cart, $customerDetails, $userId) {
            $user = $userId ? User::find($userId) : null;
            
            // Get B2B prices for all cart items in bulk (single query)
            $productQuantities = $cart->items->pluck('quantity', 'product_id')->toArray();
            $products = \App\Models\Product::whereIn('id', array_keys($productQuantities))->get();
            
            $productsWithQty = $products->map(function ($product) use ($productQuantities) {
                $product->quantity = $productQuantities[$product->id] ?? 1;
                return $product;
            });
            
            $prices = $this->pricingService->getBulkPrices($productsWithQty, $user);

            // Calculate subtotal using B2B prices
            $subtotal = 0;
            $lineItems = [];
            
            foreach ($cart->items as $item) {
                $priceInfo = $prices[$item->product_id] ?? ['price' => $item->product->base_price];
                $unitPrice = $priceInfo['price'];
                $lineTotal = $item->quantity * $unitPrice;
                $subtotal += $lineTotal;
                
                $lineItems[$item->product_id] = [
                    'quantity' => $item->quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ];
            }

            // B2B pricing already includes discounts
            $discountPercent = 0;
            $discountAmount = 0;
            
            // Check if using customer-specific pricing
            $hasCustomerPricing = collect($prices)->contains(function ($priceInfo) {
                return in_array($priceInfo['source'] ?? '', ['customer_price_list', 'volume_tier']);
            });
            
            // Apply loyalty tier discount only if no B2B pricing
            if (!$hasCustomerPricing && $user?->loyaltyTier) {
                $discountPercent = $user->loyaltyTier->discount_percent ?? 0;
                if ($discountPercent > 0) {
                    $discountAmount = $subtotal * ($discountPercent / 100);
                }
            }
            
            $totalAmount = $subtotal - $discountAmount;

            // Create Order with pending_payment status
            $order = Order::create([
                'user_id' => $userId,
                'order_number' => 'WA-' . strtoupper(uniqid()), // WA prefix for WhatsApp orders
                'status' => Order::STATUS_PENDING_PAYMENT,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tier_discount_percent' => $discountPercent,
                'total_amount' => $totalAmount,
                'payment_method' => PaymentLog::METHOD_WHATSAPP,
                'payment_status' => Order::PAYMENT_PENDING,
                'shipping_address' => $customerDetails['address'],
                'shipping_method' => 'standard', 
                'shipping_cost' => 0,
                'notes' => $this->formatNotes($customerDetails),
            ]);

            // Create Order Items with FEFO batch allocation
            foreach ($cart->items as $item) {
                $lineItem = $lineItems[$item->product_id];
                
                // Allocate stock from batches using FEFO algorithm
                try {
                    $allocations = $this->inventoryService->allocateStock(
                        $item->product_id,
                        $item->quantity,
                        $order->order_number
                    );
                } catch (\Exception $e) {
                    Log::error('FEFO allocation failed for WhatsApp order', [
                        'order' => $order->order_number,
                        'product_id' => $item->product_id,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }

                $order->items()->create([
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $lineItem['unit_price'],
                    'total_price' => $lineItem['line_total'],
                    'batch_allocations' => $allocations, // Store FEFO allocations for BPOM
                ]);
            }

            // Create Payment Log for WhatsApp checkout
            PaymentLog::create([
                'order_id' => $order->id,
                'payment_method' => PaymentLog::METHOD_WHATSAPP,
                'amount' => $totalAmount,
                'currency' => 'IDR',
                'status' => PaymentLog::STATUS_PENDING,
                'metadata' => [
                    'customer_name' => $customerDetails['name'],
                    'customer_phone' => $customerDetails['phone'],
                    'initiated_at' => now()->toIso8601String(),
                    'user_agent' => request()->userAgent(),
                ],
            ]);

            // Load items for message generation
            $order->load('items.product');

            // Generate WhatsApp URL
            $whatsappNumber = config('services.whatsapp.business_number', '6281234567890');
            $message = $order->generateWhatsAppMessage();
            $encodedMessage = rawurlencode($message);
            $whatsappUrl = "https://wa.me/{$whatsappNumber}?text={$encodedMessage}";

            return [
                'order' => $order,
                'whatsapp_url' => $whatsappUrl,
            ];
        });
    }

    /**
     * Complete an order (Simulate Payment Success)
     * Calculates points and updates user tier.
     */
    public function completeOrder(Order $order): void
    {
        if ($order->payment_status === 'paid' || !$order->user_id) {
            return;
        }

        DB::transaction(function () use ($order) {
            $order->update(['payment_status' => 'paid', 'status' => 'processing']);

            $user = User::find($order->user_id);
            if (!$user) return;

            // 1. Calculate Points
            // Rule: 1 Point per 10.000 spent
            $basePoints = floor($order->total_amount / 10000);
            
            // Apply Tier Multiplier
            $multiplier = $user->loyaltyTier->point_multiplier ?? 1.0;
            $earnedPoints = floor($basePoints * $multiplier);

            if ($earnedPoints > 0) {
                // 2. Record Transaction
                $user->pointTransactions()->create([
                    'order_id' => $order->id,
                    'amount' => $earnedPoints,
                    'type' => 'earn',
                    'description' => "Order #{$order->order_number}",
                ]);

                // 3. Update User Balance
                $user->increment('points', $earnedPoints);
            }

            // 4. Update Total Spend & Check Tier Upgrade
            $previousTierId = $user->loyalty_tier_id;
            $user->increment('total_spend', $order->total_amount);
            $this->checkTierUpgrade($user, $previousTierId);

            // 5. Send payment received notification (queued)
            $order->load('pointTransactions');
            $user->notify(new PaymentReceived($order));
        });
    }

    /**
     * Confirm WhatsApp payment (called by admin)
     */
    public function confirmWhatsAppPayment(Order $order, int $adminUserId, ?string $referenceNumber = null): bool
    {
        return DB::transaction(function () use ($order, $adminUserId, $referenceNumber) {
            // Update payment log
            $paymentLog = $order->paymentLogs()->latest()->first();
            if ($paymentLog) {
                $paymentLog->confirm($adminUserId, $referenceNumber);
            }

            // Update order status
            $order->update([
                'payment_status' => Order::PAYMENT_PAID,
                'status' => Order::STATUS_PROCESSING,
            ]);

            // Complete order (award points, update tier)
            if ($order->user_id) {
                $this->completeOrder($order);
            }

            return true;
        });
    }

    protected function checkTierUpgrade(User $user, ?int $previousTierId = null): void
    {
        $user->refresh(); // Get fresh total_spend
        
        // Find highest eligible tier
        $targetTier = LoyaltyTier::where('min_spend', '<=', $user->total_spend)
            ->orderByDesc('min_spend')
            ->first();

        if ($targetTier && $targetTier->id !== $user->loyalty_tier_id) {
            $previousTier = $previousTierId 
                ? LoyaltyTier::find($previousTierId) 
                : $user->loyaltyTier;
            
            $user->update(['loyalty_tier_id' => $targetTier->id]);
            
            // Notify user of tier upgrade (queued)
            if ($previousTier) {
                $user->notify(new TierUpgraded($previousTier, $targetTier));
            }
        }
    }

    protected function formatNotes(array $details): string
    {
        return "Name: {$details['name']}\nPhone: {$details['phone']}\nNotes: " . ($details['notes'] ?? '-');
    }
}
