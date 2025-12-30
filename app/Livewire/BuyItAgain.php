<?php

namespace App\Livewire;

use App\Services\CartService;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\OrderItem;

class BuyItAgain extends Component
{
    protected CartService $cartService;

    public function boot(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function addToCart($productId)
    {
        $product = \App\Models\Product::find($productId);
        
        if (!$product) {
            return;
        }

        // Use CartService with B2B pricing integration
        $this->cartService->addItem($productId);
        
        $this->dispatch('cart-updated');
        $this->dispatch('product-added-to-cart', name: $product->name);
        $this->dispatch('toggle-cart'); // Open cart drawer
    }
    
    public function render()
    {
        // Get unique products from user's completed orders
        $purchasedProducts = collect();
        
        if (Auth::check()) {
            $purchasedProducts = OrderItem::whereHas('order', function ($query) {
                $query->where('user_id', Auth::id())
                      ->where('status', 'completed');
            })
            ->with('product.brand')
            ->get()
            ->pluck('product')
            ->filter() // Remove nulls if product was deleted
            ->unique('id')
            ->take(6); // Limit to 6 products for display
        }
        
        return view('livewire.buy-it-again', [
            'products' => $purchasedProducts
        ]);
    }
}
