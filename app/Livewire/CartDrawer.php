<?php

namespace App\Livewire;

use App\Services\CartService;
use Livewire\Attributes\On;
use Livewire\Component;

class CartDrawer extends Component
{
    public $isOpen = false;

    #[On('cart-updated')]
    public function render(CartService $cartService)
    {
        $cart = $cartService->getCart();
        
        return view('livewire.cart-drawer', [
            'items' => $cart?->items ?? collect(),
            'subtotal' => $cartService->getSubtotal(),
        ]);
    }

    #[On('toggle-cart')]
    public function toggle()
    {
        $this->isOpen = !$this->isOpen;
    }

    public function removeItem(CartService $cartService, int $itemId)
    {
        $cartService->removeItem($itemId);
        $this->dispatch('cart-updated');
    }

    public function updateQuantity(CartService $cartService, int $itemId, int $quantity)
    {
        $cartService->updateQuantity($itemId, $quantity);
        $this->dispatch('cart-updated');
    }
}
