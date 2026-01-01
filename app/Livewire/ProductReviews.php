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

        // OPTIMIZED: Single query for stats instead of multiple COUNT queries
        // Fetches total, average, and distribution in one database call
        $stats = Review::where('product_id', $this->product->id)
            ->approved()
            ->selectRaw('COUNT(*) as total, AVG(rating) as average, rating')
            ->groupBy('rating')
            ->get();

        $totalReviews = $stats->sum('total');
        $averageRating = $totalReviews > 0 
            ? round($stats->sum(fn($s) => $s->rating * $s->total) / $totalReviews, 1) 
            : 0;

        // Build distribution from grouped stats
        $ratingCounts = $stats->pluck('total', 'rating');
        $distribution = [];
        for ($i = 5; $i >= 1; $i--) {
            $count = $ratingCounts->get($i, 0);
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
