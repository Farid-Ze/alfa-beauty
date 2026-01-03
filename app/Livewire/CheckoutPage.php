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
    public string $debugError = ''; // Debug: expose exception messages

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
        try {
            $this->stockErrors = $this->cartService->validateStock();
            
            if (!empty($this->stockErrors)) {
                $productNames = collect($this->stockErrors)->pluck('product_name')->join(', ');
                session()->flash('error', __('checkout.stock_error', ['products' => $productNames]));
                \Log::info('Checkout validation failed: stock errors', ['errors' => $this->stockErrors]);
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            $this->debugError = 'Stock: ' . $e->getMessage();
            \Log::error('Stock validation exception', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            session()->flash('error', 'Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate MOQ and order increments before checkout.
     * Auto-fixes invalid quantities and notifies user.
     * Returns true after auto-fix (allows checkout to proceed).
     */
    protected function validateMOQBeforeCheckout(): bool
    {
        try {
            // Get violations and auto-fix them
            $this->moqViolations = $this->cartService->validateMOQ(true);
            
            if (!empty($this->moqViolations)) {
                $productNames = collect($this->moqViolations)->pluck('product_name')->join(', ');
                session()->flash('warning', __('checkout.moq_adjusted', ['products' => $productNames]));
                \Log::info('MOQ adjusted during checkout', ['products' => $productNames]);
                
                // Refresh prices after quantity adjustment
                $this->priceChanges = array_merge(
                    $this->priceChanges, 
                    $this->cartService->refreshPrices()
                );
                
                // After auto-fix, still allow checkout to proceed
                // The quantities have been corrected, so we return true
            }
            
            return true;
        } catch (\Exception $e) {
            $this->debugError = 'MOQ: ' . $e->getMessage();
            \Log::error('MOQ validation exception', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            session()->flash('error', 'Error: ' . $e->getMessage());
            return false;
        }
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
        
        // Debug: track validation results
        $this->debugError = "moq:{$moqValid},stock:{$stockValid}";
        
        $result = $moqValid && $stockValid;
        \Log::info('validateBeforeOrder result', [
            'moqValid' => $moqValid,
            'stockValid' => $stockValid,
            'result' => $result,
        ]);
        
        return $result;
    }

    /**
     * Standard order placement (legacy flow)
     */
    public function placeOrder(OrderService $orderService)
    {
        \Log::info('placeOrder called', [
            'user_id' => \Auth::id(),
            'auth_check' => \Auth::check(),
        ]);
        
        $this->validate();

        $cart = $this->cartService->getCart();
        
        \Log::info('placeOrder cart state', [
            'cart_exists' => $cart !== null,
            'cart_id' => $cart?->id,
            'cart_user_id' => $cart?->user_id,
            'cart_session_id' => $cart?->session_id,
            'items_count' => $cart?->items?->count() ?? 0,
        ]);
        
        if (!$cart || $cart->items->isEmpty()) {
            \Log::warning('placeOrder: Cart is empty or null');
            session()->flash('error', 'Keranjang belanja kosong. Silakan tambahkan produk terlebih dahulu.');
            return;
        }

        // Comprehensive validation: MOQ, stock, and price refresh
        if (!$this->validateBeforeOrder()) {
            \Log::info('placeOrder: validateBeforeOrder failed');
            // Error message already set by validation methods
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
            
            // Dispatch event for Alpine.js fallback listener
            $this->dispatch('checkout-success', url: $successUrl);
            
            // Primary redirect via JS injection
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
        \Log::info('checkoutViaWhatsApp called', ['user_id' => \Auth::id()]);
        
        $this->validate();

        $cart = $this->cartService->getCart();
        if (!$cart || $cart->items->isEmpty()) {
            \Log::warning('checkoutViaWhatsApp: Cart is empty');
            session()->flash('error', 'Keranjang belanja kosong');
            return;
        }

        // Comprehensive validation: MOQ, stock, and price refresh
        if (!$this->validateBeforeOrder()) {
            \Log::info('checkoutViaWhatsApp: validateBeforeOrder failed');
            // Error message already set by validation methods
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
            
            // Dispatch event for Alpine.js fallback listener
            $this->dispatch('checkout-success', url: $successUrl);
            
            // Primary redirect via JS injection
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

