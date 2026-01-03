<?php

namespace App\Livewire;

use App\Models\Order;
use App\Services\CartService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

use App\Services\OrderService;

class CheckoutPage extends Component
{
    public string $name = '';
    public string $phone = '';
    public string $address = '';
    public string $notes = '';
    public array $stockErrors = [];
    public array $priceChanges = [];
    public array $moqViolations = [];

    protected CartService $cartService;

    protected $rules = [
        'name' => 'required|min:3',
        'phone' => 'required|min:10',
        'address' => 'required|min:10',
        'notes' => 'nullable|string',
    ];

    public function boot(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function mount()
    {
        if ($this->cartService->getItemCount() === 0) {
            return redirect('/');
        }

        // Pre-fill user data if logged in
        if (Auth::check()) {
            $user = Auth::user();
            $this->name = $user->name;
            $this->phone = $user->phone ?? '';
            
            // Only refresh B2B prices for logged-in users
            // This is the expensive call - skip for guests
            $this->priceChanges = $this->cartService->refreshPrices();
        }
        
        // Skip stock validation on mount - will validate before checkout
        // This saves multiple database queries on initial load
        $this->stockErrors = [];
    }

    /**
     * Validate stock before checkout
     */
    protected function validateStockBeforeCheckout(): bool
    {
        $this->stockErrors = $this->cartService->validateStock();
        
        if (!empty($this->stockErrors)) {
            $productNames = collect($this->stockErrors)->pluck('product_name')->join(', ');
            session()->flash('error', __('checkout.stock_error', ['products' => $productNames]));
            return false;
        }
        
        return true;
    }

    /**
     * Validate MOQ and order increments before checkout.
     * Auto-fixes invalid quantities and notifies user.
     */
    protected function validateMOQBeforeCheckout(): bool
    {
        // Get violations and auto-fix them
        $this->moqViolations = $this->cartService->validateMOQ(true);
        
        if (!empty($this->moqViolations)) {
            $productNames = collect($this->moqViolations)->pluck('product_name')->join(', ');
            session()->flash('warning', __('checkout.moq_adjusted', ['products' => $productNames]));
            
            // Refresh prices after quantity adjustment
            $this->priceChanges = array_merge(
                $this->priceChanges, 
                $this->cartService->refreshPrices()
            );
            
            return false;
        }
        
        return true;
    }

    /**
     * Perform all validations before order creation.
     * Returns true only if all validations pass.
     */
    protected function validateBeforeOrder(): bool
    {
        // First validate MOQ - this may adjust quantities
        $moqValid = $this->validateMOQBeforeCheckout();
        
        // Then validate stock - must be done after MOQ adjustment
        $stockValid = $this->validateStockBeforeCheckout();
        
        // Final price refresh to ensure accuracy
        $this->priceChanges = $this->cartService->refreshPrices();
        
        return $moqValid && $stockValid;
    }

    /**
     * Standard order placement (legacy flow)
     */
    public function placeOrder(OrderService $orderService)
    {
        $this->validate();

        $cart = $this->cartService->getCart();
        if (!$cart || $cart->items->isEmpty()) {
            return redirect('/');
        }

        // Comprehensive validation: MOQ, stock, and price refresh
        if (!$this->validateBeforeOrder()) {
            return;
        }

        try {
            $customerDetails = [
                'name' => $this->name,
                'phone' => $this->phone,
                'address' => $this->address,
                'notes' => $this->notes,
            ];

            $idempotencyKey = $this->buildIdempotencyKey('order', $cart, $customerDetails);
            $requestId = request()?->attributes?->get('request_id') ?: (string) Str::uuid();

            $order = $orderService->createFromCart($cart, $customerDetails, Auth::id(), $idempotencyKey, $requestId);

            // Trigger point calculation & tier upgrade (non-blocking)
            // If this fails, order is still valid - just log the error
            if (Auth::check()) {
                try {
                    $orderService->completeOrder($order);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('Order completion (loyalty) failed, but order was created', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Clear Cart and Dispatch
            $this->cartService->clearCart();
            $this->dispatch('cart-updated');

            // Use JavaScript redirect for maximum compatibility with serverless
            $successUrl = route('checkout.success', ['order' => $order->id]);
            $this->js("window.location.href = '" . $successUrl . "'");

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Checkout placeOrder failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
            session()->flash('error', __('checkout.order_error'));
        }
    }

    /**
     * WhatsApp checkout flow (primary B2B flow)
     * 
     * Creates order with pending_payment status and redirects to WhatsApp
     * with pre-filled order message.
     */
    public function checkoutViaWhatsApp(OrderService $orderService)
    {
        $this->validate();

        $cart = $this->cartService->getCart();
        if (!$cart || $cart->items->isEmpty()) {
            return redirect('/');
        }

        // Comprehensive validation: MOQ, stock, and price refresh
        if (!$this->validateBeforeOrder()) {
            return;
        }

        try {
            $customerDetails = [
                'name' => $this->name,
                'phone' => $this->phone,
                'address' => $this->address,
                'notes' => $this->notes,
            ];

            // Create order and get WhatsApp URL
            $idempotencyKey = $this->buildIdempotencyKey('whatsapp', $cart, $customerDetails);
            $requestId = request()?->attributes?->get('request_id') ?: (string) Str::uuid();

            $result = $orderService->createWhatsAppOrder($cart, $customerDetails, Auth::id(), $idempotencyKey, $requestId);

            // Store order ID and WhatsApp URL in session for success page
            session()->put('whatsapp_order_id', $result['order']->id);
            session()->put('whatsapp_order_number', $result['order']->order_number);
            session()->put('whatsapp_url', $result['whatsapp_url']);

            // Clear Cart
            $this->cartService->clearCart();
            $this->dispatch('cart-updated');

            // Use JavaScript redirect for maximum compatibility with serverless
            $successUrl = route('checkout.success', ['order' => $result['order']->id]);
            $this->js("window.location.href = '" . $successUrl . "'");

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Checkout via WhatsApp failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
            session()->flash('error', __('checkout.whatsapp_error'));
        }
    }

    public function render()
    {
        // Get detailed cart with B2B pricing
        $cartData = $this->cartService->getDetailedCart();
        
        // Calculate savings from B2B pricing
        $totalSavings = collect($cartData['items'])
            ->filter(fn($item) => isset($item['discount_percent']) && $item['discount_percent'] > 0)
            ->sum(fn($item) => ($item['original_price'] - $item['unit_price']) * $item['quantity']);

        return view('livewire.checkout-page', [
            'cartItems' => $cartData['items'],
            'subtotal' => $cartData['subtotal'],
            'stockErrors' => $this->stockErrors,
            'priceChanges' => $this->priceChanges,
            'totalSavings' => $totalSavings,
        ]);
    }

    protected function buildIdempotencyKey(string $channel, $cart, array $customerDetails): string
    {
        $userId = Auth::id();
        $cartId = $cart?->id ?? 'no-cart';
        $sessionId = session()->getId();

        $items = $cart?->items
            ?->map(fn($item) => ['product_id' => (int) $item->product_id, 'quantity' => (int) $item->quantity])
            ->sortBy('product_id')
            ->values()
            ->toArray() ?? [];

        $customerFingerprint = [
            'name' => (string) ($customerDetails['name'] ?? ''),
            'phone' => (string) ($customerDetails['phone'] ?? ''),
            'address' => (string) ($customerDetails['address'] ?? ''),
        ];

        return hash('sha256', implode(':', [
            'checkout',
            $channel,
            (string) $cartId,
            json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            json_encode($customerFingerprint, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            (string) ($userId ?? 'guest'),
            (string) $sessionId,
        ]));
    }
}

