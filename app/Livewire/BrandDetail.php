<?php

namespace App\Livewire;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * @method \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory layout(string $view, array $params = [])
 */
class BrandDetail extends Component
{
    public Brand $brand;
    public $featuredProduct;
    public $otherBrands;
    public $productCount;
    public $totalStock;

    public function mount(string $slug)
    {
        // OPTIMIZED: Single query with stats via withCount and subquery
        $this->brand = Brand::where('slug', $slug)
            ->withCount(['products as product_count' => function ($query) {
                $query->whereRaw('is_active = true');
            }])
            ->addSelect([
                'total_stock' => DB::table('products')
                    ->selectRaw('COALESCE(SUM(stock), 0)')
                    ->whereColumn('brand_id', 'brands.id')
                    ->whereRaw('is_active = true')
            ])
            ->firstOrFail();
        
        // Extract stats from brand model
        $this->productCount = $this->brand->product_count;
        $this->totalStock = $this->brand->total_stock ?? 0;
        
        // Get featured product (single query with fallback)
        $this->featuredProduct = Product::where('brand_id', $this->brand->id)
            ->whereRaw('is_active = true')
            ->orderByRaw('is_featured DESC') // Featured first, then any
            ->first();
        
        // Get other brands for switcher
        $this->otherBrands = Brand::where('id', '!=', $this->brand->id)
            ->whereRaw('is_featured = true')
            ->orderBy('sort_order')
            ->take(3)
            ->get();
    }

    public function render()
    {
        /** @phpstan-ignore method.notFound */
        return view('livewire.brand-detail') // @phpstan-ignore-next-line
            ->layout('components.layouts.app', [
                'title' => $this->brand->name . ' - Alfa Beauty'
            ]);
    }
}
