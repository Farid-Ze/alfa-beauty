<?php

namespace App\Livewire;

use App\Services\CartService;
use Livewire\Attributes\On;
use Livewire\Component;

class CartCounter extends Component
{
    protected CartService $cartService;

    public function boot(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    #[On('cart-updated')]
    public function refresh()
    {
        // Triggers re-render to update count
    }

    public function render()
    {
        return view('livewire.cart-counter', [
            'count' => $this->cartService->getItemCount()
        ]);
    }
}
