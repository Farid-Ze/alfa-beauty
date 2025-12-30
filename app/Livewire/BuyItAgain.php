<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\OrderItem;

class BuyItAgain extends Component
{
    public function addToCart($productId)
    {
        $product = \App\Models\Product::find($productId);
        
        if (!$product) {
            return;
        }
        
        // Get or create cart
        $cart = session()->get('cart', []);
        
        if (isset($cart[$productId])) {
            $cart[$productId]['quantity']++;
        } else {
            $cart[$productId] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => 1,
                'image' => $product->images[0] ?? null,
            ];
        }
        
        session()->put('cart', $cart);
        
        $this->dispatch('cart-updated');
        $this->dispatch('product-added', name: $product->name);
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
            ->unique('id')
            ->take(6); // Limit to 6 products for display
        }
        
        return view('livewire.buy-it-again', [
            'products' => $purchasedProducts
        ]);
    }
}
