<?php

namespace App\Livewire;

use App\Models\Brand;
use App\Models\Product;
use Livewire\Component;

class BrandDetail extends Component
{
    public Brand $brand;
    public $featuredProduct;
    public $otherBrands;
    public $productCount;
    public $totalStock;

    public function mount(string $slug)
    {
        $this->brand = Brand::where('slug', $slug)->firstOrFail();
        
        // Get featured product (or first product)
        $this->featuredProduct = Product::where('brand_id', $this->brand->id)
            ->where('is_active', true)
            ->where('is_featured', true)
            ->first();
            
        if (!$this->featuredProduct) {
            $this->featuredProduct = Product::where('brand_id', $this->brand->id)
                ->where('is_active', true)
                ->first();
        }
        
        // Get stats
        $this->productCount = Product::where('brand_id', $this->brand->id)
            ->where('is_active', true)
            ->count();
            
        $this->totalStock = Product::where('brand_id', $this->brand->id)
            ->where('is_active', true)
            ->sum('stock');
        
        // Get other brands for switcher
        $this->otherBrands = Brand::where('id', '!=', $this->brand->id)
            ->where('is_featured', true)
            ->orderBy('sort_order')
            ->take(3)
            ->get();
    }

    public function render()
    {
        return view('livewire.brand-detail')
            ->layout('components.layouts.app', [
                'title' => $this->brand->name . ' - Alfa Beauty'
            ]);
    }
}
