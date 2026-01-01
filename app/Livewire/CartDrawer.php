<?php

namespace App\Livewire;

use App\Services\CartService;
use Livewire\Attributes\On;
use Livewire\Component;

class CartDrawer extends Component
{
    // Note: Cart drawer open/close state is managed by Alpine.js in the view
    // for smoother transitions. Livewire handles data updates only.

    protected CartService $cartService;

    public function boot(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    #[On('cart-updated')]
    public function refresh() 
    {
        // This triggers re-render to update cart data
    }

    public function render()
    {
        // Use detailed cart for B2B pricing info
        $cartData = $this->cartService->getDetailedCart();
        
        return view('livewire.cart-drawer', [
            'items' => $cartData['items'],
            'subtotal' => $cartData['subtotal'],
            'itemCount' => $cartData['item_count'],
        ]);
    }

    public function removeItem(int $itemId)
    {
        try {
            $this->cartService->removeItem($itemId);
            $this->dispatch('cart-updated');
        } catch (\Exception $e) {
            \Log::warning('Failed to remove cart item', ['item_id' => $itemId, 'error' => $e->getMessage()]);
            $this->dispatch('cart-updated'); // Still refresh cart
            session()->flash('error', __('cart.item_unavailable'));
        }
    }

    public function updateQuantity(int $itemId, int $quantity)
    {
        try {
            $this->cartService->updateQuantity($itemId, $quantity);
            $this->dispatch('cart-updated');
        } catch (\Exception $e) {
            \Log::warning('Failed to update cart quantity', ['item_id' => $itemId, 'error' => $e->getMessage()]);
            $this->dispatch('cart-updated');
            session()->flash('error', __('cart.item_unavailable'));
        }
    }

    public function incrementItem(int $itemId)
    {
        try {
            $cart = $this->cartService->getCart();
            $item = $cart?->items()->with('product')->find($itemId);
            
            if ($item && $item->product) {
                // Respect product's order_increment (default to 1)
                $increment = $item->product->order_increment ?? 1;
                $this->updateQuantity($itemId, $item->quantity + $increment);
            } else {
                // Product was deleted - remove item from cart
                $this->removeItem($itemId);
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to increment cart item', ['item_id' => $itemId, 'error' => $e->getMessage()]);
            $this->dispatch('cart-updated');
            session()->flash('error', __('cart.item_unavailable'));
        }
    }

    public function decrementItem(int $itemId)
    {
        try {
            $cart = $this->cartService->getCart();
            $item = $cart?->items()->with('product')->find($itemId);
            
            if ($item && $item->product) {
                // Respect product's order_increment and min_order_qty
                $increment = $item->product->order_increment ?? 1;
                $minQty = $item->product->min_order_qty ?? 1;
                $newQty = $item->quantity - $increment;
                
                // Don't go below minimum order quantity
                if ($newQty < $minQty) {
                    // If decrementing would go below MOQ, remove item instead
                    // This prevents stuck items that can't be decreased
                    $this->removeItem($itemId);
                } else {
                    $this->updateQuantity($itemId, $newQty);
                }
            } else {
                // Product was deleted - remove item from cart
                $this->removeItem($itemId);
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to decrement cart item', ['item_id' => $itemId, 'error' => $e->getMessage()]);
            $this->dispatch('cart-updated');
            session()->flash('error', __('cart.item_unavailable'));
        }
    }
}

