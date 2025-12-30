<?php

namespace App\Livewire;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
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

    public function render()
    {
        $query = Product::query()
            ->with(['brand', 'category']);

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
        $query->where('is_active', true);

        return view('livewire.product-list-page', [
            'products' => $query->paginate($this->perPage),
            'brands' => Brand::whereHas('products')->get(),
            'categories' => Category::whereHas('products')->get(),
        ]);
    }
}
