<div class="review-form-wrapper">
    @auth
        @if($submitted)
            <div class="review-success">
                <svg class="success-icon" width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p>{{ __('reviews.submitted_pending_approval') }}</p>
                <small>{{ __('reviews.bonus_on_approval') }}</small>
            </div>
        @elseif($alreadyReviewed)
            <div class="review-info">
                <svg class="info-icon" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p>{{ __('reviews.already_reviewed') }}</p>
            </div>
        @else
            @if(!$showForm)
                <button wire:click="toggleForm" class="btn-write-review">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                    {{ __('reviews.write_review') }}
                </button>
            @else
                <form wire:submit="submit" class="review-form">
                    <h4 class="review-form-title">{{ __('reviews.your_review') }}</h4>
                    
                    <!-- Star Rating -->
                    <div class="rating-input">
                        <label class="form-label">{{ __('reviews.rating') }}</label>
                        <div class="stars-input">
                            @for($i = 1; $i <= 5; $i++)
                                <button type="button" 
                                        wire:click="setRating({{ $i }})"
                                        class="star-btn {{ $rating >= $i ? 'active' : '' }}"
                                        aria-label="Rate {{ $i }} star">
                                    <svg width="24" height="24" fill="{{ $rating >= $i ? 'var(--gold)' : 'none' }}" stroke="var(--gold)" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                    </svg>
                                </button>
                            @endfor
                            <span class="rating-label">{{ \App\Models\Review::RATINGS[$rating] ?? '' }}</span>
                        </div>
                        @error('rating') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    <!-- Title (Optional) -->
                    <div class="form-group">
                        <label class="form-label">{{ __('reviews.title') }} <span class="optional">({{ __('reviews.optional') }})</span></label>
                        <input type="text" 
                               wire:model="title" 
                               placeholder="{{ __('reviews.title_placeholder') }}"
                               class="form-input">
                    </div>

                    <!-- Content -->
                    <div class="form-group">
                        <label class="form-label">{{ __('reviews.content') }}</label>
                        <textarea wire:model="content" 
                                  rows="4"
                                  placeholder="{{ __('reviews.content_placeholder') }}"
                                  class="form-input @error('content') error @enderror"></textarea>
                        @error('content') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-actions">
                        <button type="button" wire:click="toggleForm" class="btn btn-secondary">
                            {{ __('common.cancel') }}
                        </button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove>{{ __('reviews.submit') }}</span>
                            <span wire:loading>{{ __('common.loading') }}...</span>
                        </button>
                    </div>

                    <p class="bonus-note">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>
                        </svg>
                        {{ __('reviews.bonus_note', ['points' => 50]) }}
                    </p>
                </form>
            @endif
        @endif
    @else
        <div class="login-prompt">
            <p>{{ __('reviews.login_to_review') }}</p>
            <a href="{{ route('login') }}" class="btn btn-primary">{{ __('auth.login') }}</a>
        </div>
    @endauth
</div>

<style>
.review-form-wrapper {
    margin: 2rem 0;
    padding: 1.5rem;
    background: var(--gray-100);
    border-radius: 0.75rem;
}

.btn-write-review {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: var(--black);
    color: var(--white);
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    cursor: pointer;
    font-family: 'Inter', sans-serif;
    font-weight: 500;
    font-size: 0.875rem;
    transition: opacity 0.2s;
}

.btn-write-review:hover {
    opacity: 0.9;
}

.review-form {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.review-form-title {
    font-family: 'Instrument Serif', serif;
    font-size: 1.25rem;
    font-weight: 400;
    margin: 0;
    color: var(--black);
}

.rating-input {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.stars-input {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.star-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.25rem;
    transition: transform 0.2s;
}

.star-btn:hover {
    transform: scale(1.15);
}

.rating-label {
    margin-left: 0.75rem;
    color: var(--gray-600);
    font-size: 0.875rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.form-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--black);
}

.form-label .optional {
    font-weight: 400;
    color: var(--gray-400);
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 0.5rem;
}

.btn-secondary {
    background: var(--gray-200);
    color: var(--black);
}

.bonus-note {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8125rem;
    color: var(--gray-600);
    margin-top: 0.5rem;
}

.review-success, .review-info, .login-prompt {
    text-align: center;
    padding: 2rem 1rem;
}

.success-icon {
    color: var(--green);
    margin-bottom: 0.75rem;
}

.info-icon {
    color: var(--gray-400);
    margin-bottom: 0.5rem;
}

.review-success p, .review-info p, .login-prompt p {
    margin: 0 0 0.5rem;
    color: var(--black);
}

.review-success small {
    color: var(--gray-600);
    font-size: 0.8125rem;
}
</style>
