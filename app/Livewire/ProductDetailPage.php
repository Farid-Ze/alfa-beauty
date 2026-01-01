<?php

namespace App\Livewire;

use App\Models\Product;
use App\Services\CartService;
use App\Services\PricingService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Product Details - B2B Hair Care')]
class ProductDetailPage extends Component
{
    public $slug;
    public $quantity = 1;
    
    // Cached product data to avoid repeated queries
    protected ?Product $cachedProduct = null;
    
    // Computed pricing properties
    public $currentPrice;
    public $originalPrice;
    public $discountPercent;
    public $priceSource;
    public $priceTiers = [];

    protected CartService $cartService;
    protected PricingService $pricingService;

    public function boot(CartService $cartService, PricingService $pricingService)
    {
        $this->cartService = $cartService;
        $this->pricingService = $pricingService;
    }
    
    /**
     * Get cached product to avoid repeated queries.
     * OPTIMIZATION: Was 5+ queries per request, now 1 query.
     */
    protected function getProduct(): ?Product
    {
        if (!$this->cachedProduct) {
            $this->cachedProduct = Product::with(['brand', 'category', 'priceTiers'])
                ->where('slug', $this->slug)
                ->first();
        }
        return $this->cachedProduct;
    }
    
    /**
     * Clear cached product (use after updates that might affect product data).
     */
    protected function clearProductCache(): void
    {
        $this->cachedProduct = null;
    }

    public function mount($slug)
    {
        $this->slug = $slug;
        
        // Initialize quantity to min_order_qty
        $product = $this->getProduct();
        if (!$product) {
            abort(404);
        }
        $this->quantity = $product->min_order_qty ?? 1;
        
        $this->updatePricing();
    }

    /**
     * Called when quantity changes - update pricing for potential tier changes.
     */
    public function updatedQuantity($value)
    {
        $product = $this->getProduct();
        $minQty = $product?->min_order_qty ?? 1;
        $increment = $product?->order_increment ?? 1;
        
        $qty = max($minQty, (int) $value);
        
        // Round to nearest increment
        if ($increment > 1) {
            $qty = (int) ceil(($qty - $minQty) / $increment) * $increment + $minQty;
        }
        
        $this->quantity = $qty;
        $this->updatePricing();
    }

    /**
     * Update pricing based on current quantity.
     */
    protected function updatePricing(): void
    {
        $product = $this->getProduct();
        if (!$product) return;

        $user = Auth::user();
        $priceInfo = $this->pricingService->getPrice($product, $user, $this->quantity);

        $this->currentPrice = $priceInfo['price'];
        $this->originalPrice = $priceInfo['original_price'] ?? $product->base_price;
        $this->discountPercent = $priceInfo['discount_percent'];
        $this->priceSource = $priceInfo['source'];

        // Get volume tiers for display (already eager loaded)
        $this->priceTiers = $product->priceTiers
            ->sortBy('min_quantity')
            ->map(function ($tier) use ($product) {
                return [
                    'min_qty' => $tier->min_quantity,
                    'max_qty' => $tier->max_quantity,
                    'unit_price' => $tier->calculateUnitPrice($product->base_price),
                    'discount_percent' => $tier->discount_percent,
                    'label' => $tier->max_quantity 
                        ? "{$tier->min_quantity}-{$tier->max_quantity}" 
                        : "{$tier->min_quantity}+",
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Add product to cart with specified quantity.
     * 
     * PERFORMANCE: Single database operation for any quantity.
     * No loop = no 500 queries for 500 units!
     */
    public function addToCart()
    {
        $product = $this->getProduct();
        if (!$product) {
            abort(404);
        }
        
        // Validate stock
        if ($this->quantity > $product->stock) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => "Only {$product->stock} units available",
            ]);
            return;
        }

        // Single addItem call - no loop!
        $this->cartService->addItem($product->id, $this->quantity);

        // Dispatch events for CartDrawer and CartCounter
        $this->dispatch('cart-updated');
        $this->dispatch('product-added-to-cart', name: $product->name, quantity: $this->quantity);
        
        // Open cart drawer after adding
        $this->dispatch('toggle-cart');
        
        // Reset quantity to minimum
        $this->quantity = $product->min_order_qty ?? 1;
        $this->updatePricing();
    }

    /**
     * Increment quantity by order increment.
     */
    public function incrementQuantity()
    {
        $product = $this->getProduct();
        $increment = $product?->order_increment ?? 1;
        $this->quantity += $increment;
        $this->updatePricing();
    }

    /**
     * Decrement quantity by order increment (respecting min_order_qty).
     */
    public function decrementQuantity()
    {
        $product = $this->getProduct();
        $minQty = $product?->min_order_qty ?? 1;
        $increment = $product?->order_increment ?? 1;
        
        if ($this->quantity - $increment >= $minQty) {
            $this->quantity -= $increment;
            $this->updatePricing();
        }
    }

    public function render()
    {
        $product = $this->getProduct();
        if (!$product) {
            abort(404);
        }

        return view('livewire.product-detail-page', [
            'product' => $product,
            'hasCustomerPricing' => $this->priceSource === 'customer_price_list',
            'hasVolumePricing' => $this->priceSource === 'volume_tier',
            'hasDiscount' => $this->discountPercent > 0,
            'lineTotal' => $this->currentPrice * $this->quantity,
            'minOrderQty' => $product->min_order_qty ?? 1,
            'orderIncrement' => $product->order_increment ?? 1,
        ]);
    }
}

