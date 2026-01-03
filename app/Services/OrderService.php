<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\InventoryServiceInterface;
use App\Contracts\OrderServiceInterface;
use App\Models\Cart;
use App\Models\LoyaltyTier;
use App\Models\AuditEvent;
use App\Models\Order;
use App\Models\OrderCancellation;
use App\Models\PaymentLog;
use App\Models\User;
use App\Notifications\OrderConfirmation;
use App\Notifications\PaymentReceived;
use App\Notifications\TierUpgraded;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class OrderService implements OrderServiceInterface
{
    /** @var array<string, bool> */
    protected static array $pointTransactionsColumnCache = [];

    protected function pointTransactionsHasColumn(string $column): bool
    {
        if (!array_key_exists($column, self::$pointTransactionsColumnCache)) {
            self::$pointTransactionsColumnCache[$column] = \Illuminate\Support\Facades\Schema::hasColumn('point_transactions', $column);
        }

        return self::$pointTransactionsColumnCache[$column];
    }

    public function __construct(
        protected readonly InventoryServiceInterface $inventoryService, 
        protected readonly PricingService $pricingService,
        protected readonly CartService $cartService
    ) {}

    /**
     * Interface compatibility: create an order from the current user's cart.
     * This codebase primarily uses createFromCart() which is cart-driven.
     */
    public function createOrder(User $user, array $shippingData, ?string $notes = null, ?string $idempotencyKey = null, ?string $requestId = null): Order
    {
        $cart = $user->cart()->with('items.product')->firstOrFail();

        $customerDetails = [
            'name' => $user->name,
            'phone' => $shippingData['phone'] ?? ($user->phone ?? ''),
            'address' => $shippingData['address'] ?? '',
            'notes' => $notes,
        ];

        return $this->createFromCart($cart, $customerDetails, $user->id, $idempotencyKey, $requestId);
    }

    public function getOrder(int $orderId, User $user): ?Order
    {
        $order = Order::with(['items.product', 'pointTransactions'])->find($orderId);
        if (!$order) {
            return null;
        }

        if (!$user->can('view', $order)) {
            return null;
        }

        return $order;
    }

    public function getUserOrders(User $user, int $perPage = 10)
    {
        return Order::query()
            ->where('user_id', $user->id)
            ->with(['items.product', 'pointTransactions'])
            ->latest()
            ->paginate($perPage);
    }
    
    /**
     * Validate and correct MOQ violations before order creation.
     * 
     * DEFENSIVE: Even if cart should be validated, we re-check here
     * to prevent invalid orders from being created.
     * 
     * @param Cart $cart
     * @return array List of corrections made
     */
    protected function validateAndCorrectMOQ(Cart $cart): array
    {
        $corrections = [];
        $products = \App\Models\Product::whereIn('id', $cart->items->pluck('product_id'))->get()->keyBy('id');
        
        foreach ($cart->items as $item) {
            $product = $products[$item->product_id] ?? null;
            if (!$product) continue;
            
            $minQty = $product->min_order_qty ?? 1;
            $increment = $product->order_increment ?? 1;
            $originalQty = $item->quantity;
            $correctedQty = $originalQty;
            
            // Ensure minimum order quantity
            if ($correctedQty < $minQty) {
                $correctedQty = $minQty;
            }
            
            // Ensure quantity is in valid increments
            if ($increment > 1 && $correctedQty > $minQty) {
                $remainder = ($correctedQty - $minQty) % $increment;
                if ($remainder !== 0) {
                    $correctedQty = $correctedQty + ($increment - $remainder);
                }
            }
            
            if ($correctedQty !== $originalQty) {
                // Update cart item
                $item->update(['quantity' => $correctedQty]);
                
                $corrections[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'original_qty' => $originalQty,
                    'corrected_qty' => $correctedQty,
                    'reason' => $originalQty < $minQty ? 'below_moq' : 'invalid_increment',
                ];
                
                Log::info('OrderService: Corrected MOQ violation', [
                    'product_id' => $product->id,
                    'original' => $originalQty,
                    'corrected' => $correctedQty,
                ]);
            }
        }
        
        return $corrections;
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
    public function createFromCart(Cart $cart, array $customerDetails, ?int $userId = null, ?string $idempotencyKey = null, ?string $requestId = null): Order
    {
        return DB::transaction(function () use ($cart, $customerDetails, $userId, $idempotencyKey, $requestId) {
            $user = $userId ? User::find($userId) : null;

            if ($idempotencyKey) {
                $existing = Order::where('idempotency_key', $idempotencyKey)->first();
                if ($existing) {
                    Log::info('OrderService: Idempotency hit (createFromCart)', [
                        'order_id' => $existing->id,
                        'order_number' => $existing->order_number,
                        'idempotency_key' => $idempotencyKey,
                        'user_id' => $userId,
                    ]);

                    return $existing;
                }
            }

            $requestId = $requestId ?: (request()?->attributes?->get('request_id') ?: (string) Str::uuid());
            
            Log::info('OrderService: Starting order creation', [
                'request_id' => $requestId,
                'idempotency_key' => $idempotencyKey,
                'user_id' => $userId,
                'cart_id' => $cart->id,
                'item_count' => $cart->items->count(),
            ]);
            
            // DEFENSIVE: Validate and correct MOQ violations before processing
            $moqCorrections = $this->validateAndCorrectMOQ($cart);
            if (!empty($moqCorrections)) {
                Log::info('OrderService: MOQ corrections applied', [
                    'corrections' => $moqCorrections,
                ]);
                // Refresh cart items after corrections
                $cart->refresh();
                $cart->load('items.product');
            }
            
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
                    'discount_percent' => $priceInfo['discount_percent'] ?? null,
                    'pricing_meta' => $priceInfo,
                ];
            }

            // B2B pricing already includes discounts from customer price lists
            // Only apply additional tier discount if not already using customer-specific pricing
            $discountPercent = 0;
            $discountAmount = 0;
            
            // Check if any item used customer-specific or tier-based pricing (already discounted)
            $hasPricingWithDiscount = collect($lineItems)->contains(function ($item) {
                return in_array($item['price_source'] ?? '', ['customer_price_list', 'volume_tier', 'loyalty_tier']);
            });
            
            // If using base prices with loyalty tier, apply tier discount
            if (!$hasPricingWithDiscount && $user?->loyaltyTier) {
                $discountPercent = $user->loyaltyTier->discount_percent ?? 0;
                if ($discountPercent > 0) {
                    $discountAmount = $subtotal * ($discountPercent / 100);
                }
            }
            
            $totalAmount = $subtotal - $discountAmount;

            // Create Order
            try {
                $order = Order::create([
                    'request_id' => $requestId,
                    'idempotency_key' => $idempotencyKey,
                    'user_id' => $userId,
                    'order_number' => 'ORD-' . strtoupper(uniqid()),
                    'status' => Order::STATUS_PENDING,
                    'subtotal' => $subtotal,
                    'discount_amount' => $discountAmount,
                    'discount_percent' => $discountPercent,
                    'total_amount' => $totalAmount,
                    'payment_method' => 'manual_transfer',
                    'payment_status' => Order::PAYMENT_PENDING,
                    'shipping_address' => $customerDetails['address'],
                    'shipping_method' => 'standard',
                    'shipping_cost' => 0,
                    'notes' => $this->formatNotes($customerDetails),
                ]);
            } catch (QueryException $e) {
                if ($idempotencyKey && $this->isUniqueConstraintViolation($e)) {
                    $existing = Order::where('idempotency_key', $idempotencyKey)->first();
                    if ($existing) {
                        Log::info('OrderService: Idempotency collision resolved (createFromCart)', [
                            'order_id' => $existing->id,
                            'order_number' => $existing->order_number,
                            'idempotency_key' => $idempotencyKey,
                            'user_id' => $userId,
                        ]);

                        return $existing;
                    }
                }

                throw $e;
            }

            $this->auditEvent([
                'request_id' => $requestId,
                'idempotency_key' => $idempotencyKey,
                'actor_user_id' => $userId,
                'action' => 'order.created',
                'entity_type' => Order::class,
                'entity_id' => $order->id,
                'meta' => [
                    'order_number' => $order->order_number,
                    'channel' => 'web',
                    'total_amount' => $order->total_amount,
                    'item_count' => $cart->items->count(),
                ],
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
                    'price_source' => $lineItem['price_source'] ?? 'base_price',
                    'original_unit_price' => $lineItem['original_price'] ?? null,
                    'discount_percent' => $lineItem['discount_percent'] ?? null,
                    'pricing_meta' => $lineItem['pricing_meta'] ?? null,
                    'total_price' => $lineItem['line_total'],
                    'batch_allocations' => $allocations, // Store FEFO allocations for BPOM
                    // GOVERNANCE: Price snapshot isolation
                    'price_locked_at' => now(),
                    'pricing_metadata' => $lineItem['pricing_meta'] ?? null,
                ]);
            }

            // Send order confirmation notification (queued)
            if ($user) {
                $user->notify(new OrderConfirmation($order));
            }

            Log::info('OrderService: Order created successfully', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'user_id' => $userId,
                'total_amount' => $totalAmount,
                'item_count' => $cart->items->count(),
            ]);

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
    public function createWhatsAppOrder(Cart $cart, array $customerDetails, ?int $userId = null, ?string $idempotencyKey = null, ?string $requestId = null): array
    {
        return DB::transaction(function () use ($cart, $customerDetails, $userId, $idempotencyKey, $requestId) {
            $user = $userId ? User::find($userId) : null;

            if ($idempotencyKey) {
                $existing = Order::where('idempotency_key', $idempotencyKey)->first();
                if ($existing) {
                    $existing->loadMissing('items.product');

                    $whatsappNumber = config('services.whatsapp.business_number');
                    if (empty($whatsappNumber)) {
                        $whatsappNumber = '6281234567890';
                    }

                    $message = $existing->generateWhatsAppMessage();
                    $encodedMessage = rawurlencode($message);
                    $whatsappUrl = "https://wa.me/{$whatsappNumber}?text={$encodedMessage}";

                    Log::info('OrderService: Idempotency hit (createWhatsAppOrder)', [
                        'order_id' => $existing->id,
                        'order_number' => $existing->order_number,
                        'idempotency_key' => $idempotencyKey,
                        'user_id' => $userId,
                    ]);

                    return [
                        'order' => $existing,
                        'whatsapp_url' => $whatsappUrl,
                    ];
                }
            }

            $requestId = $requestId ?: (request()?->attributes?->get('request_id') ?: (string) Str::uuid());

            Log::info('OrderService: Starting WhatsApp order creation', [
                'request_id' => $requestId,
                'idempotency_key' => $idempotencyKey,
                'user_id' => $userId,
                'cart_id' => $cart->id,
                'item_count' => $cart->items->count(),
            ]);
            
            // DEFENSIVE: Validate and correct MOQ violations before processing
            $moqCorrections = $this->validateAndCorrectMOQ($cart);
            if (!empty($moqCorrections)) {
                // Refresh cart items after corrections
                $cart->refresh();
                $cart->load('items.product');
            }
            
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
                    'price_source' => $priceInfo['source'] ?? 'base_price',
                    'original_price' => $priceInfo['original_price'] ?? $unitPrice,
                    'discount_percent' => $priceInfo['discount_percent'] ?? null,
                    'pricing_meta' => $priceInfo,
                ];
            }

            // B2B pricing already includes discounts
            $discountPercent = 0;
            $discountAmount = 0;
            
            // Check if using customer-specific or tier-based pricing (already discounted)
            $hasPricingWithDiscount = collect($prices)->contains(function ($priceInfo) {
                return in_array($priceInfo['source'] ?? '', ['customer_price_list', 'volume_tier', 'loyalty_tier']);
            });
            
            // Apply loyalty tier discount only if no B2B pricing
            if (!$hasPricingWithDiscount && $user?->loyaltyTier) {
                $discountPercent = $user->loyaltyTier->discount_percent ?? 0;
                if ($discountPercent > 0) {
                    $discountAmount = $subtotal * ($discountPercent / 100);
                }
            }
            
            $totalAmount = $subtotal - $discountAmount;

            // Create Order with pending_payment status
            try {
                $order = Order::create([
                    'request_id' => $requestId,
                    'idempotency_key' => $idempotencyKey,
                    'user_id' => $userId,
                    'order_number' => 'WA-' . strtoupper(uniqid()), // WA prefix for WhatsApp orders
                    'status' => Order::STATUS_PENDING_PAYMENT,
                    'subtotal' => $subtotal,
                    'discount_amount' => $discountAmount,
                    'discount_percent' => $discountPercent,
                    'total_amount' => $totalAmount,
                    'payment_method' => PaymentLog::METHOD_WHATSAPP,
                    'payment_status' => Order::PAYMENT_PENDING,
                    'shipping_address' => $customerDetails['address'],
                    'shipping_method' => 'standard',
                    'shipping_cost' => 0,
                    'notes' => $this->formatNotes($customerDetails),
                ]);
            } catch (QueryException $e) {
                if ($idempotencyKey && $this->isUniqueConstraintViolation($e)) {
                    $existing = Order::where('idempotency_key', $idempotencyKey)->first();
                    if ($existing) {
                        $existing->loadMissing('items.product');

                        $whatsappNumber = config('services.whatsapp.business_number');
                        if (empty($whatsappNumber)) {
                            $whatsappNumber = '6281234567890';
                        }

                        $message = $existing->generateWhatsAppMessage();
                        $encodedMessage = rawurlencode($message);
                        $whatsappUrl = "https://wa.me/{$whatsappNumber}?text={$encodedMessage}";

                        Log::info('OrderService: Idempotency collision resolved (createWhatsAppOrder)', [
                            'order_id' => $existing->id,
                            'order_number' => $existing->order_number,
                            'idempotency_key' => $idempotencyKey,
                            'user_id' => $userId,
                        ]);

                        return [
                            'order' => $existing,
                            'whatsapp_url' => $whatsappUrl,
                        ];
                    }
                }

                throw $e;
            }

            $this->auditEvent([
                'request_id' => $requestId,
                'idempotency_key' => $idempotencyKey,
                'actor_user_id' => $userId,
                'action' => 'order.created',
                'entity_type' => Order::class,
                'entity_id' => $order->id,
                'meta' => [
                    'order_number' => $order->order_number,
                    'channel' => 'whatsapp',
                    'total_amount' => $order->total_amount,
                    'item_count' => $cart->items->count(),
                ],
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
                    'price_source' => $lineItem['price_source'] ?? 'base_price',
                    'original_unit_price' => $lineItem['original_price'] ?? null,
                    'discount_percent' => $lineItem['discount_percent'] ?? null,
                    'pricing_meta' => $lineItem['pricing_meta'] ?? null,
                    'total_price' => $lineItem['line_total'],
                    'batch_allocations' => $allocations, // Store FEFO allocations for BPOM
                    // GOVERNANCE: Price snapshot isolation
                    'price_locked_at' => now(),
                    'pricing_metadata' => $lineItem['pricing_meta'] ?? null,
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
                    'request_id' => $order->request_id,
                    'idempotency_key' => $order->idempotency_key,
                    'customer_name' => $customerDetails['name'],
                    'customer_phone' => $customerDetails['phone'],
                    'initiated_at' => now()->toIso8601String(),
                    'user_agent' => request()->userAgent(),
                ],
            ]);

            // Load items for message generation
            $order->load('items.product');

            // Generate WhatsApp URL - use placeholder if not configured
            $whatsappNumber = config('services.whatsapp.business_number');
            if (empty($whatsappNumber)) {
                // Use a placeholder number - should be configured in production
                \Log::warning('WhatsApp business number not configured - using placeholder');
                $whatsappNumber = '6281234567890';
            }
            
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
    public function completeOrder(Order $order, array $paymentData = []): Order
    {
        if ($order->payment_status === Order::PAYMENT_PAID || !$order->user_id) {
            return $order;
        }

        Log::info('OrderService: Completing order', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'user_id' => $order->user_id,
        ]);

        DB::transaction(function () use ($order) {
            /** @var Order $locked */
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($locked->payment_status === Order::PAYMENT_PAID || !$locked->user_id) {
                return;
            }

            $locked->update(['payment_status' => Order::PAYMENT_PAID, 'status' => Order::STATUS_PROCESSING]);

            $user = User::whereKey($locked->user_id)->lockForUpdate()->first();
            if (!$user) {
                return;
            }

            // 1. Calculate Points
            // Rule: 1 Point per 10.000 spent
            $basePoints = floor($locked->total_amount / 10000);

            // Apply Tier Multiplier
            $multiplier = $user->loyaltyTier?->point_multiplier ?? 1.0;
            $earnedPoints = (int) floor($basePoints * $multiplier);

            if ($earnedPoints > 0) {
                $created = false;
                try {
                    $txIdempotencyKey = "earn:order:{$locked->id}:user:{$user->id}";
                    $txLookup = ['order_id' => $locked->id, 'type' => 'earn'];
                    if ($this->pointTransactionsHasColumn('idempotency_key')) {
                        $txLookup = ['idempotency_key' => $txIdempotencyKey];
                    }

                    $txData = [
                        'order_id' => $locked->id,
                        'amount' => $earnedPoints,
                        'type' => 'earn',
                        'description' => "Order #{$locked->order_number}",
                    ];
                    if ($this->pointTransactionsHasColumn('request_id')) {
                        $txData['request_id'] = request()?->attributes?->get('request_id');
                    }
                    if ($this->pointTransactionsHasColumn('idempotency_key')) {
                        $txData['idempotency_key'] = $txIdempotencyKey;
                    }

                    $tx = $user->pointTransactions()->firstOrCreate($txLookup, $txData);
                    $created = $tx->wasRecentlyCreated;
                } catch (\Illuminate\Database\QueryException $e) {
                    if (!$this->isUniqueConstraintViolation($e)) {
                        throw $e;
                    }
                }

                if ($created) {
                    // 3. Update User Balance
                    $user->increment('points', $earnedPoints);

                    if (isset($tx) && $this->pointTransactionsHasColumn('balance_after')) {
                        $tx->forceFill(['balance_after' => $user->fresh()->points])->save();
                    }
                }
            }

            // 4. Update Total Spend & Check Tier Upgrade
            $previousTierId = $user->loyalty_tier_id;
            $user->increment('total_spend', $locked->total_amount);
            $this->checkTierUpgrade($user, $previousTierId);

            // 5. Send payment received notification (queued)
            $locked->load('pointTransactions');
            $user->notify(new PaymentReceived($locked));

            Log::info('OrderService: Order completed, points awarded', [
                'order_id' => $locked->id,
                'order_number' => $locked->order_number,
                'user_id' => $user->id,
                'points_earned' => $earnedPoints ?? 0,
                'new_total_spend' => $user->total_spend,
            ]);
        });

        return $order->fresh();
    }

    /**
     * Cancel an order in an idempotent + concurrency-safe way.
     * - Locks the order row
     * - Ensures a single OrderCancellation row per order
     * - Releases FEFO allocations exactly once
     * - Writes governance audit events (non-fatal)
     */
    public function cancelOrder(Order $order, string $reasonCode, ?string $notes = null, ?int $cancelledBy = null): Order
    {
        return DB::transaction(function () use ($order, $reasonCode, $notes, $cancelledBy) {
            /** @var Order $locked */
            $locked = Order::whereKey($order->id)->lockForUpdate()->with(['items.product', 'paymentLogs'])->firstOrFail();

            /** @var OrderCancellation|null $cancellation */
            $cancellation = $locked->cancellation()->first();

            // Idempotency: if already cancelled (or has cancellation record), do not re-run side effects.
            if ($locked->status === Order::STATUS_CANCELLED && $cancellation && $cancellation->inventory_released_at) {
                return $locked->fresh(['cancellation', 'items']);
            }

            // Safety: only allow cancellation when eligible unless it's already cancelled.
            if ($locked->status !== Order::STATUS_CANCELLED && !$locked->canBeCancelled()) {
                throw new \RuntimeException('Order cannot be cancelled');
            }

            if (!$cancellation) {
                try {
                    $cancellation = OrderCancellation::firstOrCreate(
                        ['order_id' => $locked->id],
                        [
                            'cancelled_by' => $cancelledBy,
                            'reason_code' => $reasonCode,
                            'reason_notes' => $notes,
                            'refund_amount' => (float) ($locked->amount_paid ?? 0),
                            'refund_status' => ((float) ($locked->amount_paid ?? 0)) > 0 ? 'pending' : 'completed',
                        ]
                    );
                } catch (\Illuminate\Database\QueryException $e) {
                    if (!$this->isUniqueConstraintViolation($e)) {
                        throw $e;
                    }
                    $cancellation = OrderCancellation::where('order_id', $locked->id)->firstOrFail();
                }
            }

            // Release FEFO allocations exactly once
            if (!$cancellation->inventory_released_at) {
                $allocations = [];
                foreach ($locked->items as $item) {
                    if (!empty($item->batch_allocations)) {
                        $itemAllocs = collect($item->batch_allocations)->map(function ($alloc) use ($item) {
                            $alloc['product_id'] = $item->product_id;
                            return $alloc;
                        })->toArray();

                        $allocations = array_merge($allocations, $itemAllocs);
                    } else {
                        // Fallback for legacy items with no batch data
                        $allocations[] = [
                            'batch_id' => null,
                            'batch_number' => 'LEGACY-NO-BATCH',
                            'quantity' => $item->quantity,
                            'product_id' => $item->product_id,
                        ];
                    }
                }

                if (!empty($allocations)) {
                    $this->inventoryService->releaseStock(
                        $allocations,
                        "Order #{$locked->order_number} cancelled ({$reasonCode})"
                    );
                }

                $cancellation->forceFill(['inventory_released_at' => now()])->save();
            }

            if ($locked->status !== Order::STATUS_CANCELLED) {
                $locked->status = Order::STATUS_CANCELLED;
            }

            // Keep order notes append-only
            if ($notes) {
                $locked->notes = trim((string) $locked->notes . "\n\n[CANCELLED] " . now()->toDateTimeString() . "\n" . $notes);
            }

            $locked->save();

            // Update payment logs (best-effort)
            try {
                $locked->paymentLogs()->update([
                    'status' => PaymentLog::STATUS_CANCELLED,
                    'metadata->cancelled_at' => now()->toIso8601String(),
                    'metadata->cancelled_reason' => $reasonCode,
                ]);
            } catch (\Throwable $e) {
                Log::warning('PaymentLog cancel update failed', [
                    'order_id' => $locked->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $this->auditEvent([
                'request_id' => request()?->attributes?->get('request_id'),
                'idempotency_key' => "cancel:order:{$locked->id}",
                'actor_user_id' => $cancelledBy,
                'action' => 'order.cancelled',
                'entity_type' => Order::class,
                'entity_id' => $locked->id,
                'meta' => [
                    'order_number' => $locked->order_number,
                    'reason_code' => $reasonCode,
                    'notes' => $notes,
                    'inventory_released_at' => $cancellation->inventory_released_at?->toIso8601String(),
                ],
            ]);

            return $locked->fresh(['cancellation', 'items']);
        });
    }

    /**
     * Confirm WhatsApp payment (called by admin)
     */
    public function confirmWhatsAppPayment(Order $order, int $adminUserId, ?string $referenceNumber = null): bool
    {
        return DB::transaction(function () use ($order, $adminUserId, $referenceNumber) {
            /** @var Order $locked */
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($locked->payment_status === Order::PAYMENT_PAID) {
                return true;
            }

            // Update payment log
            /** @var \App\Models\PaymentLog|null $paymentLog */
            $paymentLog = $locked->paymentLogs()->latest()->first();
            if ($paymentLog) {
                /** @phpstan-ignore method.notFound */
                $paymentLog->confirm($adminUserId, $referenceNumber);
            }

            // Complete order BEFORE updating status (award points, update tier)
            // This must be done before setting payment_status to 'paid' because
            // completeOrder checks for pending status
            if ($locked->user_id && $locked->payment_status !== Order::PAYMENT_PAID) {
                $this->awardPointsAndUpdateSpend($locked);
            }

            // Update order status
            $locked->update([
                'payment_status' => Order::PAYMENT_PAID,
                'status' => Order::STATUS_PROCESSING,
            ]);

            $this->auditEvent([
                'request_id' => request()?->attributes?->get('request_id'),
                'idempotency_key' => $locked->idempotency_key,
                'actor_user_id' => $adminUserId,
                'action' => 'order.payment_confirmed',
                'entity_type' => Order::class,
                'entity_id' => $locked->id,
                'meta' => [
                    'order_number' => $locked->order_number,
                    'payment_method' => $locked->payment_method,
                    'reference_number' => $referenceNumber,
                ],
            ]);

            return true;
        });
    }

    protected function isUniqueConstraintViolation(QueryException $e): bool
    {
        $sqlState = $e->errorInfo[0] ?? null;
        $driverCode = $e->errorInfo[1] ?? null;

        // MySQL: SQLSTATE 23000 / driver 1062, Postgres: SQLSTATE 23505
        return $sqlState === '23000' || $sqlState === '23505' || $driverCode === 1062;
    }

    protected function auditEvent(array $payload): void
    {
        try {
            if (!Schema::hasTable('audit_events')) {
                return;
            }

            AuditEvent::create($payload);
        } catch (\Throwable $e) {
            Log::warning('AuditEvent write failed', [
                'error' => $e->getMessage(),
                'action' => $payload['action'] ?? null,
                'entity_type' => $payload['entity_type'] ?? null,
                'entity_id' => $payload['entity_id'] ?? null,
            ]);
        }
    }

    /**
     * Award points and update total spend for an order.
     * Extracted from completeOrder to avoid status check issues.
     */
    protected function awardPointsAndUpdateSpend(Order $order): void
    {
        $user = User::whereKey($order->user_id)->lockForUpdate()->first();
        if (!$user) return;

        // 1. Calculate Points
        // Rule: 1 Point per 10.000 spent
        $basePoints = floor($order->total_amount / 10000);
        
        // Apply Tier Multiplier
        $multiplier = $user->loyaltyTier?->point_multiplier ?? 1.0;
        $earnedPoints = (int) floor($basePoints * $multiplier);

        if ($earnedPoints > 0) {
            $created = false;
            try {
                $txIdempotencyKey = "earn:order:{$order->id}:user:{$user->id}";
                $txLookup = ['order_id' => $order->id, 'type' => 'earn'];
                if ($this->pointTransactionsHasColumn('idempotency_key')) {
                    $txLookup = ['idempotency_key' => $txIdempotencyKey];
                }

                $txData = [
                    'order_id' => $order->id,
                    'amount' => $earnedPoints,
                    'type' => 'earn',
                    'description' => "Order #{$order->order_number}",
                ];
                if ($this->pointTransactionsHasColumn('request_id')) {
                    $txData['request_id'] = request()?->attributes?->get('request_id');
                }
                if ($this->pointTransactionsHasColumn('idempotency_key')) {
                    $txData['idempotency_key'] = $txIdempotencyKey;
                }

                $tx = $user->pointTransactions()->firstOrCreate($txLookup, $txData);
                $created = $tx->wasRecentlyCreated;
            } catch (\Illuminate\Database\QueryException $e) {
                if (!$this->isUniqueConstraintViolation($e)) {
                    throw $e;
                }
            }

            if ($created) {
                // 3. Update User Balance
                $user->increment('points', $earnedPoints);

                if (isset($tx) && $this->pointTransactionsHasColumn('balance_after')) {
                    $tx->forceFill(['balance_after' => $user->fresh()->points])->save();
                }
            }
        }

        // 4. Update Total Spend & Check Tier Upgrade
        $previousTierId = $user->loyalty_tier_id;
        $user->increment('total_spend', $order->total_amount);
        $this->checkTierUpgrade($user, $previousTierId);

        // 5. Send payment received notification (queued)
        $order->load('pointTransactions');
        $user->notify(new PaymentReceived($order));
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

            $this->auditEvent([
                'request_id' => request()?->attributes?->get('request_id'),
                'idempotency_key' => $user->id ? "loyalty_tier.change:user:{$user->id}:to:{$targetTier->id}" : null,
                'actor_user_id' => null,
                'action' => 'user.loyalty_tier_changed',
                'entity_type' => User::class,
                'entity_id' => $user->id,
                'meta' => [
                    'from_tier_id' => $previousTier?->id,
                    'from_tier_name' => $previousTier?->name,
                    'to_tier_id' => $targetTier->id,
                    'to_tier_name' => $targetTier->name,
                    'total_spend' => $user->total_spend,
                    'source' => 'order',
                ],
            ]);
            
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
