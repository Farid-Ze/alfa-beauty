<?php

namespace App\Livewire;

use App\Models\Product;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Product Details - B2B Hair Care')]
class ProductDetailPage extends Component
{
    public $slug;
    public $quantity = 1;

    public function mount($slug)
    {
        $this->slug = $slug;
    }

    public function addToCart(int $qty, \App\Services\CartService $cartService)
    {
        $product = Product::where('slug', $this->slug)->firstOrFail();
        
        // Add item using the unified CartService (database-backed)
        // We can optimize this if CartService supports adding multiple at once, 
        // but for now loop is fine or we update CartService.
        // Assuming CartService add item adds 1 by default, let's just loop for now or check service.
        for ($i = 0; $i < $qty; $i++) {
            $cartService->addItem($product->id);
        }

        // Dispatch events for CartDrawer and CartCounter
        $this->dispatch('cart-updated');
        $this->dispatch('product-added-to-cart', name: $product->name);
        
        // Open cart drawer after adding
        $this->dispatch('toggle-cart');
    }

    public function render()
    {
        return view('livewire.product-detail-page', [
            'product' => Product::where('slug', $this->slug)->firstOrFail(),
        ]);
    }
}
