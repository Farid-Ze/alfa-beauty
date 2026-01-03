<?php

namespace App\Livewire;

use App\Models\Product;
use App\Services\PricingService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ProductList extends Component
{
    protected PricingService $pricingService;
    
    public function boot(PricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }
    
    public function render()
    {
        $products = Product::with(['brand', 'priceTiers'])
            ->whereRaw('is_featured = true')
            ->limit(4)
            ->get();

        if ($products->isEmpty()) {
            $products = Product::with(['brand', 'priceTiers'])->limit(4)->get();
        }
        
        // Fetch B2B prices in bulk for all products
        $prices = [];
        if ($products->isNotEmpty()) {
            $prices = $this->pricingService->getBulkPrices($products, Auth::user());
        }

        return view('livewire.product-list', [
            'products' => $products,
            'prices' => $prices,
        ]);
    }
}
