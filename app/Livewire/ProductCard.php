<?php

namespace App\Livewire;

use App\Models\Product;
use App\Services\CartService;
use Livewire\Component;

class ProductCard extends Component
{
    public Product $product;
    
    /**
     * Optional B2B price info passed from parent.
     * Structure: ['price' => float, 'original_price' => float, 'source' => string, 'discount_percent' => float|null]
     */
    public ?array $priceInfo = null;

    public function addToCart(CartService $cartService): void
    {
        try {
            // Refresh product to get current stock
            $this->product->refresh();
            
            // Validate stock availability
            if ($this->product->stock <= 0) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => __('cart.out_of_stock', ['product' => $this->product->name]),
                ]);
                return;
            }
            
            // Validate minimum order quantity
            $minQty = $this->product->min_order_qty ?? 1;
            if ($this->product->stock < $minQty) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => __('cart.insufficient_stock', ['product' => $this->product->name]),
                ]);
                return;
            }
            
            $cartService->addItem($this->product->id);
            
            $this->dispatch('cart-updated');
            $this->dispatch('product-added-to-cart', name: $this->product->name);
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
        return view('livewire.product-card');
    }
}
