<?php

namespace App\Livewire;

use Livewire\Component;

class ProductCard extends Component
{
    public \App\Models\Product $product;

    public function addToCart(\App\Services\CartService $cartService)
    {
        $cartService->addItem($this->product->id);
        
        $this->dispatch('cart-updated');
        $this->dispatch('product-added-to-cart', name: $this->product->name);
        $this->dispatch('toggle-cart'); // Open cart drawer
    }

    public function render()
    {
        return view('livewire.product-card');
    }
}
