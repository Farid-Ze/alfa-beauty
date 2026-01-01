<?php

namespace App\Livewire;

use App\Services\CartService;
use App\Services\PricingService;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\OrderItem;

class BuyItAgain extends Component
{
    protected CartService $cartService;
    protected PricingService $pricingService;

    public function boot(CartService $cartService, PricingService $pricingService)
    {
        $this->cartService = $cartService;
        $this->pricingService = $pricingService;
    }

    public function addToCart($productId): void
    {
        try {
            $product = \App\Models\Product::find($productId);
            
            if (!$product) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => __('cart.product_not_found'),
                ]);
                return;
            }

            // Validate stock availability
            if ($product->stock <= 0) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => __('cart.out_of_stock', ['product' => $product->name]),
                ]);
                return;
            }
            
            // Validate minimum order quantity
            $minQty = $product->min_order_qty ?? 1;
            if ($product->stock < $minQty) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => __('cart.insufficient_stock', ['product' => $product->name]),
                ]);
                return;
            }

            // Use CartService with B2B pricing integration
            $this->cartService->addItem($productId);
            
            $this->dispatch('cart-updated');
            $this->dispatch('product-added-to-cart', name: $product->name);
            $this->dispatch('toggle-cart');
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => __('cart.add_failed'),
            ]);
            report($e);
        }
    }
    
    public function render()
    {
        // Get unique products from user's completed orders
        $purchasedProducts = collect();
        $pricesMap = [];
        
        if (Auth::check()) {
            $purchasedProducts = OrderItem::whereHas('order', function ($query) {
                $query->where('user_id', Auth::id())
                      ->where('status', 'completed');
            })
            ->with(['product.brand', 'product.priceTiers'])
            ->get()
            ->pluck('product')
            ->filter() // Remove nulls if product was deleted
            ->unique('id')
            ->take(6); // Limit to 6 products for display
            
            // Fetch all prices in one batch using getBulkPrices
            if ($purchasedProducts->isNotEmpty()) {
                $pricesMap = $this->pricingService->getBulkPrices(
                    $purchasedProducts,
                    Auth::user()
                );
            }
        }
        
        return view('livewire.buy-it-again', [
            'products' => $purchasedProducts,
            'prices' => $pricesMap,
        ]);
    }
}
