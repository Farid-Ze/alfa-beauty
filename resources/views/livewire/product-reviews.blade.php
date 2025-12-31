<div class="product-reviews">
    <!-- Summary Section -->
    <div class="reviews-summary">
        <div class="rating-overview">
            <div class="rating-big">
                <span class="rating-number">{{ number_format($averageRating, 1) }}</span>
                <div class="rating-stars">
                    @for($i = 1; $i <= 5; $i++)
                        <svg width="20" height="20" fill="{{ $i <= round($averageRating) ? 'var(--gold)' : 'none' }}" stroke="var(--gold)" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                        </svg>
                    @endfor
                </div>
            </div>
            <p class="total-reviews">{{ $totalReviews }} {{ __('reviews.reviews_count') }}</p>
        </div>

        <div class="rating-distribution">
            @foreach($distribution as $stars => $data)
                <div class="rating-bar">
                    <span class="stars-label">{{ $stars }}</span>
                    <svg width="14" height="14" fill="var(--gold)" stroke="var(--gold)" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                    <div class="bar-container">
                        <div class="bar-fill" style="width: {{ $data['percent'] }}%"></div>
                    </div>
                    <span class="bar-count">{{ $data['count'] }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Reviews List -->
    <div class="reviews-list">
        @forelse($reviews as $review)
            <div class="review-card">
                <div class="review-header">
                    <div class="reviewer-info">
                        <span class="reviewer-name">{{ $review->user?->name ?? 'Anonim' }}</span>
                        @if($review->is_verified)
                            <span class="verified-badge">
                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ __('reviews.verified_buyer') }}
                            </span>
                        @endif
                    </div>
                    <div class="review-meta">
                        <div class="review-stars">
                            @for($i = 1; $i <= 5; $i++)
                                <svg width="14" height="14" fill="{{ $i <= $review->rating ? 'var(--gold)' : 'none' }}" stroke="var(--gold)" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                </svg>
                            @endfor
                        </div>
                        <span class="review-date">{{ $review->created_at->diffForHumans() }}</span>
                    </div>
                </div>

                @if($review->title)
                    <h4 class="review-title">{{ $review->title }}</h4>
                @endif

                <p class="review-content">{{ $review->content }}</p>
            </div>
        @empty
            <div class="no-reviews">
                <svg width="48" height="48" fill="none" stroke="var(--gray-300)" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <p>{{ __('reviews.no_reviews_yet') }}</p>
                <span class="hint">{{ __('reviews.be_first') }}</span>
            </div>
        @endforelse
    </div>

    <!-- Load More -->
    @if($hasMore)
        <div class="load-more">
            <button wire:click="loadMore" class="btn btn-secondary">
                {{ __('reviews.load_more') }}
            </button>
        </div>
    @endif
</div>

<style>
.product-reviews {
    margin: 2.5rem 0;
}

.reviews-summary {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 2.5rem;
    padding: 1.5rem;
    background: var(--gray-100);
    border-radius: 0.75rem;
    margin-bottom: 1.5rem;
}

@media (max-width: 768px) {
    .reviews-summary {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
}

.rating-overview {
    text-align: center;
    padding-right: 2rem;
    border-right: 1px solid var(--gray-200);
}

@media (max-width: 768px) {
    .rating-overview {
        padding-right: 0;
        border-right: none;
        border-bottom: 1px solid var(--gray-200);
        padding-bottom: 1.5rem;
    }
}

.rating-big {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.rating-number {
    font-family: 'Instrument Serif', serif;
    font-size: 3rem;
    font-weight: 400;
    color: var(--black);
    line-height: 1;
}

.rating-stars {
    display: flex;
    gap: 0.125rem;
}

.total-reviews {
    color: var(--gray-600);
    font-size: 0.875rem;
    margin-top: 0.5rem;
}

.rating-distribution {
    display: flex;
    flex-direction: column;
    gap: 0.625rem;
    justify-content: center;
}

.rating-bar {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.stars-label {
    width: 1rem;
    text-align: right;
    font-size: 0.875rem;
    color: var(--gray-600);
}

.bar-container {
    flex: 1;
    height: 6px;
    background: var(--gray-200);
    border-radius: 3px;
    overflow: hidden;
}

.bar-fill {
    height: 100%;
    background: var(--gold);
    border-radius: 3px;
    transition: width 0.3s ease;
}

.bar-count {
    width: 1.5rem;
    text-align: right;
    font-size: 0.8125rem;
    color: var(--gray-500);
}

.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.review-card {
    padding: 1.25rem;
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: 0.5rem;
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.75rem;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.reviewer-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.reviewer-name {
    font-weight: 600;
    color: var(--black);
}

.verified-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    background: #dcfce7;
    color: #16a34a;
    padding: 0.125rem 0.5rem;
    border-radius: 1rem;
    font-weight: 500;
}

.review-meta {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.review-stars {
    display: flex;
    gap: 0.125rem;
}

.review-date {
    color: var(--gray-500);
    font-size: 0.8125rem;
}

.review-title {
    font-family: 'Inter', sans-serif;
    font-weight: 600;
    font-size: 0.9375rem;
    margin: 0 0 0.5rem;
    color: var(--black);
}

.review-content {
    color: var(--gray-700);
    line-height: 1.6;
    font-size: 0.9375rem;
    margin: 0;
}

.no-reviews {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--gray-500);
}

.no-reviews p {
    margin: 1rem 0 0.25rem;
    font-weight: 500;
    color: var(--gray-600);
}

.no-reviews .hint {
    font-size: 0.875rem;
}

.load-more {
    text-align: center;
    margin-top: 1.5rem;
}
</style>
