<?php

namespace App\Livewire;

use App\Models\AuditEvent;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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
    public ?int $orderId = null;
    
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
        $this->orderId = $order?->id;
        
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
        $hasOrderFromMount = $this->orderId !== null;
        $hasPaidOrder = Order::where('user_id', Auth::id())
            ->whereHas('items', fn($q) => $q->where('product_id', $this->product->id))
            ->where('payment_status', 'paid')
            ->exists();
        
        $isVerified = $hasOrderFromMount || $hasPaidOrder;

        $review = Review::firstOrCreate(
            [
                'user_id' => Auth::id(),
                'product_id' => $this->product->id,
            ],
            [
                'order_id' => $this->orderId,
                'rating' => $this->rating,
                'title' => $this->title ?: null,
                'content' => $this->content,
                'is_verified' => $isVerified,
                'is_approved' => false, // Requires admin moderation
                'points_awarded' => false,
            ]
        );

        if (!$review->wasRecentlyCreated) {
            $this->alreadyReviewed = true;
            $this->submitted = false;
            $this->showForm = false;

            session()->flash('info', __('reviews.already_reviewed'));
            return;
        }

        $this->auditEvent([
            'request_id' => request()?->attributes?->get('request_id'),
            'idempotency_key' => "review.submit:user:" . Auth::id() . ":product:" . $this->product->id,
            'actor_user_id' => Auth::id(),
            'action' => 'review.submitted',
            'entity_type' => Review::class,
            'entity_id' => $review->id,
            'meta' => [
                'product_id' => $this->product->id,
                'order_id' => $this->orderId,
                'rating' => $this->rating,
                'is_verified' => $isVerified,
            ],
        ]);

        $this->submitted = true;
        $this->showForm = false;
        
        // Dispatch success event
        $this->dispatch('review-submitted');
        
        session()->flash('success', __('reviews.submitted_pending_approval'));
    }

    protected function auditEvent(array $payload): void
    {
        try {
            if (!Schema::hasTable('audit_events')) {
                return;
            }

            AuditEvent::create($payload);
        } catch (\Throwable $e) {
            Log::warning('AuditEvent write failed', [
                'error' => $e->getMessage(),
                'action' => $payload['action'] ?? null,
                'entity_type' => $payload['entity_type'] ?? null,
                'entity_id' => $payload['entity_id'] ?? null,
            ]);
        }
    }

    public function render()
    {
        return view('livewire.review-form');
    }
}
