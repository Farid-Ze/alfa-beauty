<?php

namespace App\Livewire;

use App\Services\CartService;
use Livewire\Attributes\On;
use Livewire\Component;

class CartCounter extends Component
{
    #[On('cart-updated')]
    public function render(CartService $cartService)
    {
        return view('livewire.cart-counter', [
            'count' => $cartService->getItemCount()
        ]);
    }
}
