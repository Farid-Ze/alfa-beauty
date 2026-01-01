<?php

namespace App\Livewire;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\PricingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Product Catalog - B2B Hair Care')]
class ProductListPage extends Component
{
    use WithPagination;

    #[Url]
    public $search = '';

    #[Url]
    public $selectedBrands = [];

    #[Url]
    public $selectedCategories = [];

    #[Url]
    public $sort = 'latest';

    #[Url]
    public $priceMin = null;

    #[Url]
    public $priceMax = null;

    #[Url]
    public $perPage = 12;
    
    protected PricingService $pricingService;
    
    /**
     * Cache TTL for brands/categories (5 minutes)
     */
    private const FILTER_CACHE_TTL = 300;
    
    public function boot(PricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    /**
     * Get brands with products (cached to prevent repeated queries on re-render)
     * Uses Livewire computed property + application cache
     */
    #[Computed]
    public function brands(): Collection
    {
        return Cache::remember('product_list_brands', self::FILTER_CACHE_TTL, function () {
            return Brand::whereHas('products', function ($query) {
                $query->where('is_active', true);
            })->orderBy('name')->get();
        });
    }

    /**
     * Get categories with products (cached to prevent repeated queries on re-render)
     * Uses Livewire computed property + application cache
     */
    #[Computed]
    public function categories(): Collection
    {
        return Cache::remember('product_list_categories', self::FILTER_CACHE_TTL, function () {
            return Category::whereHas('products', function ($query) {
                $query->where('is_active', true);
            })->orderBy('name')->get();
        });
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedSelectedBrands()
    {
        $this->resetPage();
    }

    public function updatedSelectedCategories()
    {
        $this->resetPage();
    }

    public function updatedSort()
    {
        $this->resetPage();
    }

    /**
     * Clear all filters and reset to default state.
     */
    public function clearFilters(): void
    {
        $this->selectedCategories = [];
        $this->selectedBrands = [];
        $this->priceMin = null;
        $this->priceMax = null;
        $this->search = '';
        $this->resetPage();
    }

    public function render()
    {
        $query = Product::query()
            ->with(['brand', 'category', 'priceTiers']);

        // Search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('sku', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        // Filter by Brand
        if (!empty($this->selectedBrands)) {
            $query->whereIn('brand_id', $this->selectedBrands);
        }

        // Filter by Category
        if (!empty($this->selectedCategories)) {
            $query->whereIn('category_id', $this->selectedCategories);
        }

        // Filter by Price
        if ($this->priceMin) {
            $query->where('base_price', '>=', $this->priceMin);
        }
        if ($this->priceMax) {
            $query->where('base_price', '<=', $this->priceMax);
        }

        // Sorting
        switch ($this->sort) {
            case 'price_asc':
                $query->orderBy('base_price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('base_price', 'desc');
                break;
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'latest':
            default:
                $query->latest();
                break;
        }

        // Only active/in-stock logic if needed? For B2B, usually show all but mark OOS.
        $query->whereRaw('is_active = true');

        $products = $query->paginate($this->perPage);
        
        // Fetch B2B prices in bulk for all products on this page
        // Convert paginator items to collection for getBulkPrices
        $prices = [];
        if ($products->isNotEmpty()) {
            $prices = $this->pricingService->getBulkPrices($products->getCollection(), Auth::user());
        }

        return view('livewire.product-list-page', [
            'products' => $products,
            'prices' => $prices,
            'brands' => $this->brands,
            'categories' => $this->categories,
        ]);
    }
}
