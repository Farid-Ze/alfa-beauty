<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\Review;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * ProductReviews Component
 * 
 * Displays approved product reviews with statistics.
 */
class ProductReviews extends Component
{
    use WithPagination;

    public Product $product;
    public int $perPage = 5;

    public function mount(Product $product)
    {
        $this->product = $product;
    }

    public function loadMore()
    {
        $this->perPage += 5;
    }

    public function render()
    {
        $reviews = Review::where('product_id', $this->product->id)
            ->approved()
            ->with('user')
            ->latest()
            ->take($this->perPage)
            ->get();

        $totalReviews = Review::getReviewCount($this->product->id);
        $averageRating = Review::getAverageRating($this->product->id);

        // Rating distribution
        $distribution = [];
        for ($i = 5; $i >= 1; $i--) {
            $count = Review::where('product_id', $this->product->id)
                ->approved()
                ->where('rating', $i)
                ->count();
            $distribution[$i] = [
                'count' => $count,
                'percent' => $totalReviews > 0 ? round(($count / $totalReviews) * 100) : 0,
            ];
        }

        return view('livewire.product-reviews', [
            'reviews' => $reviews,
            'totalReviews' => $totalReviews,
            'averageRating' => $averageRating,
            'distribution' => $distribution,
            'hasMore' => $totalReviews > $this->perPage,
        ]);
    }
}
