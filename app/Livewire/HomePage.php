<?php

namespace App\Livewire;

use App\Models\Brand;
use App\Models\Product;
use Livewire\Component;

class HomePage extends Component
{
    public $brands;

    public function mount()
    {
        $this->brands = Brand::where('is_featured', true)
            ->orderBy('sort_order')
            ->take(4)
            ->get()
            ->map(function ($brand) {
                $brand->product_count = Product::where('brand_id', $brand->id)
                    ->where('is_active', true)
                    ->count();
                $brand->total_stock = Product::where('brand_id', $brand->id)
                    ->where('is_active', true)
                    ->sum('stock');
                return $brand;
            });
    }

    public function render()
    {
        return view('livewire.home-page');
    }
}
