<?php

namespace App\Livewire;

use App\Models\Brand;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class HomePage extends Component
{
    public $brands;

    public function mount()
    {
        // OPTIMIZED: Single query with withCount and subquery for stock sum
        // Eliminates N+1 problem (was 8+ queries, now 1 query)
        $this->brands = Brand::whereRaw('is_featured = true')
            ->orderBy('sort_order')
            ->take(4)
            ->withCount(['products as product_count' => function ($query) {
                $query->whereRaw('is_active = true');
            }])
            ->addSelect([
                'total_stock' => DB::table('products')
                    ->selectRaw('COALESCE(SUM(stock), 0)')
                    ->whereColumn('brand_id', 'brands.id')
                    ->whereRaw('is_active = true')
            ])
            ->get();
    }

    public function render()
    {
        return view('livewire.home-page');
    }
}
