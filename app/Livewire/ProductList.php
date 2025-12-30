<?php

namespace App\Livewire;

use Livewire\Component;

class ProductList extends Component
{
    public function render()
    {
        $products = \App\Models\Product::with('brand')
            ->whereRaw('is_featured = true')
            ->limit(4)
            ->get();

        if ($products->isEmpty()) {
            $products = \App\Models\Product::with('brand')->limit(4)->get();
        }

        return view('livewire.product-list', [
            'products' => $products
        ]);
    }
}
