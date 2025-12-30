<?php

namespace App\Livewire;

use App\Services\CartService;
use Livewire\Attributes\On;
use Livewire\Component;

class CartDrawer extends Component
{
    public $isOpen = false;

    protected CartService $cartService;

    public function boot(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    #[On('cart-updated')]
    public function refresh() 
    {
        // This triggers re-render
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

    #[On('toggle-cart')]
    public function toggle()
    {
        $this->isOpen = !$this->isOpen;
    }

    public function removeItem(int $itemId)
    {
        $this->cartService->removeItem($itemId);
        $this->dispatch('cart-updated');
    }

    public function updateQuantity(int $itemId, int $quantity)
    {
        $this->cartService->updateQuantity($itemId, $quantity);
        $this->dispatch('cart-updated');
    }

    public function incrementItem(int $itemId)
    {
        $cart = $this->cartService->getCart();
        $item = $cart?->items()->find($itemId);
        
        if ($item) {
            $this->updateQuantity($itemId, $item->quantity + 1);
        }
    }

    public function decrementItem(int $itemId)
    {
        $cart = $this->cartService->getCart();
        $item = $cart?->items()->find($itemId);
        
        if ($item && $item->quantity > 1) {
            $this->updateQuantity($itemId, $item->quantity - 1);
        }
    }
}

