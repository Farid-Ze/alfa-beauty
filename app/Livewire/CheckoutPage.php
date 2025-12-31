<?php

namespace App\Livewire;

use App\Models\Order;
use App\Services\CartService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

use App\Services\OrderService;

class CheckoutPage extends Component
{
    public $name;
    public $phone;
    public $address;
    public $notes;
    public array $stockErrors = [];
    public array $priceChanges = [];

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
     * Standard order placement (legacy flow)
     */
    public function placeOrder(OrderService $orderService)
    {
        $this->validate();

        $cart = $this->cartService->getCart();
        if (!$cart || $cart->items->isEmpty()) {
            return redirect('/');
        }

        // Validate stock before proceeding
        if (!$this->validateStockBeforeCheckout()) {
            return;
        }

        try {
            $customerDetails = [
                'name' => $this->name,
                'phone' => $this->phone,
                'address' => $this->address,
                'notes' => $this->notes,
            ];

            $order = $orderService->createFromCart($cart, $customerDetails, Auth::id());

            // Trigger point calculation & tier upgrade
            // NOTE: In production, this should be called after payment confirmation
            // For demo/MVP, we simulate immediate payment success
            if (Auth::check()) {
                $orderService->completeOrder($order);
            }

            // Clear Cart and Dispatch
            $this->cartService->clearCart();
            $this->dispatch('cart-updated');

            return $this->redirectRoute('checkout.success', ['order' => $order->id]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Checkout placeOrder failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Rethrow to show error in debug mode
            throw $e;
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

        // Validate stock before proceeding
        if (!$this->validateStockBeforeCheckout()) {
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
            $result = $orderService->createWhatsAppOrder($cart, $customerDetails, Auth::id());

            // Store order ID in session for reference
            session()->put('whatsapp_order_id', $result['order']->id);
            session()->put('whatsapp_order_number', $result['order']->order_number);

            // Clear Cart
            $this->cartService->clearCart();
            $this->dispatch('cart-updated');

            // Redirect to WhatsApp (external URL, use navigate: false)
            return $this->redirect($result['whatsapp_url'], navigate: false);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Checkout via WhatsApp failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Rethrow to show error in debug mode
            throw $e;
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
}

