<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * ReviewForm Component
 * 
 * Allows verified buyers to submit product reviews.
 * Awards +50 bonus points upon admin approval.
 */
class ReviewForm extends Component
{
    public Product $product;
    public ?Order $order = null;
    
    public int $rating = 5;
    public string $title = '';
    public string $content = '';
    
    public bool $showForm = false;
    public bool $submitted = false;
    public bool $alreadyReviewed = false;

    protected $rules = [
        'rating' => 'required|integer|min:1|max:5',
        'title' => 'nullable|string|max:255',
        'content' => 'required|string|min:10|max:2000',
    ];

    public function mount(Product $product, ?Order $order = null)
    {
        $this->product = $product;
        $this->order = $order;
        
        // Check if user already reviewed this product
        if (Auth::check()) {
            $this->alreadyReviewed = Review::hasReviewed(Auth::id(), $product->id);
        }
    }

    public function setRating(int $rating)
    {
        $this->rating = $rating;
    }

    public function toggleForm()
    {
        $this->showForm = !$this->showForm;
    }

    public function submit()
    {
        if (!Auth::check()) {
            return $this->redirect(route('login'));
        }

        $this->validate();

        // Check if user has purchased this product (verified buyer)
        $isVerified = $this->order !== null || 
            Order::where('user_id', Auth::id())
                ->whereHas('items', fn($q) => $q->where('product_id', $this->product->id))
                ->where('payment_status', 'paid')
                ->exists();

        Review::create([
            'user_id' => Auth::id(),
            'product_id' => $this->product->id,
            'order_id' => $this->order?->id,
            'rating' => $this->rating,
            'title' => $this->title ?: null,
            'content' => $this->content,
            'is_verified' => $isVerified,
            'is_approved' => false, // Requires admin moderation
            'points_awarded' => false,
        ]);

        $this->submitted = true;
        $this->showForm = false;
        
        // Dispatch success event
        $this->dispatch('review-submitted');
        
        session()->flash('success', __('reviews.submitted_pending_approval'));
    }

    public function render()
    {
        return view('livewire.review-form');
    }
}
