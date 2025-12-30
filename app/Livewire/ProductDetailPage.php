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

    public function mount($slug)
    {
        $this->slug = $slug;
        $this->updatePricing();
    }

    /**
     * Called when quantity changes - update pricing for potential tier changes.
     */
    public function updatedQuantity($value)
    {
        $this->quantity = max(1, (int) $value);
        $this->updatePricing();
    }

    /**
     * Update pricing based on current quantity.
     */
    protected function updatePricing(): void
    {
        $product = Product::where('slug', $this->slug)->first();
        if (!$product) return;

        $user = Auth::user();
        $priceInfo = $this->pricingService->getPrice($product, $user, $this->quantity);

        $this->currentPrice = $priceInfo['price'];
        $this->originalPrice = $priceInfo['original_price'] ?? $product->base_price;
        $this->discountPercent = $priceInfo['discount_percent'];
        $this->priceSource = $priceInfo['source'];

        // Get volume tiers for display
        $this->priceTiers = $product->priceTiers()
            ->orderBy('min_quantity')
            ->get()
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
        $product = Product::where('slug', $this->slug)->firstOrFail();
        
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
        
        // Reset quantity
        $this->quantity = 1;
        $this->updatePricing();
    }

    /**
     * Increment quantity.
     */
    public function incrementQuantity()
    {
        $this->quantity++;
        $this->updatePricing();
    }

    /**
     * Decrement quantity (min 1).
     */
    public function decrementQuantity()
    {
        if ($this->quantity > 1) {
            $this->quantity--;
            $this->updatePricing();
        }
    }

    public function render()
    {
        $product = Product::with(['brand', 'category', 'priceTiers'])
            ->where('slug', $this->slug)
            ->firstOrFail();

        return view('livewire.product-detail-page', [
            'product' => $product,
            'hasCustomerPricing' => $this->priceSource === 'customer_price_list',
            'hasVolumePricing' => $this->priceSource === 'volume_tier',
            'hasDiscount' => $this->discountPercent > 0,
            'lineTotal' => $this->currentPrice * $this->quantity,
        ]);
    }
}

