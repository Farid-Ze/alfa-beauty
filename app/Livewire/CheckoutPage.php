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

    protected $rules = [
        'name' => 'required|min:3',
        'phone' => 'required|min:10',
        'address' => 'required|min:10',
        'notes' => 'nullable|string',
    ];

    public function mount(CartService $cartService)
    {
        if ($cartService->getItemCount() === 0) {
            return redirect('/');
        }

        // Pre-fill user data if logged in
        if (Auth::check()) {
            $user = Auth::user();
            $this->name = $user->name;
            $this->phone = $user->phone ?? '';
        }

        // Check stock on page load
        $this->stockErrors = $cartService->validateStock();
    }

    /**
     * Validate stock before checkout
     */
    protected function validateStockBeforeCheckout(CartService $cartService): bool
    {
        $this->stockErrors = $cartService->validateStock();
        
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
    public function placeOrder(CartService $cartService, OrderService $orderService)
    {
        $this->validate();

        $cart = $cartService->getCart();
        if (!$cart || $cart->items->isEmpty()) {
            return redirect('/');
        }

        // Validate stock before proceeding
        if (!$this->validateStockBeforeCheckout($cartService)) {
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
            $cartService->clearCart();
            $this->dispatch('cart-updated');

            return redirect()->route('checkout.success', ['order' => $order->id]);

        } catch (\Exception $e) {
            session()->flash('error', __('checkout.order_error') . ': ' . $e->getMessage());
        }
    }

    /**
     * WhatsApp checkout flow (primary B2B flow)
     * 
     * Creates order with pending_payment status and redirects to WhatsApp
     * with pre-filled order message.
     */
    public function checkoutViaWhatsApp(CartService $cartService, OrderService $orderService)
    {
        $this->validate();

        $cart = $cartService->getCart();
        if (!$cart || $cart->items->isEmpty()) {
            return redirect('/');
        }

        // Validate stock before proceeding
        if (!$this->validateStockBeforeCheckout($cartService)) {
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
            $cartService->clearCart();
            $this->dispatch('cart-updated');

            // Redirect to WhatsApp
            return redirect()->away($result['whatsapp_url']);

        } catch (\Exception $e) {
            session()->flash('error', __('checkout.whatsapp_error') . ': ' . $e->getMessage());
        }
    }

    public function render(CartService $cartService)
    {
        return view('livewire.checkout-page', [
            'cartItems' => $cartService->getCart()->items,
            'subtotal' => $cartService->getSubtotal(),
            'stockErrors' => $this->stockErrors,
        ]);
    }
}
