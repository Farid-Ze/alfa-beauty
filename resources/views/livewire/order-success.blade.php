<div>
    <!-- Page Hero -->
    <section class="page-hero">
        <div class="order-success-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <h1>{{ __('checkout.order_success') }}</h1>
        <p>{{ __('checkout.thank_you') }}</p>
    </section>

    <div class="orders-container order-success-container">
        <div class="order-card order-success-card">
            <div class="order-number-badge">
                {{ __('checkout.order_number') }} #{{ $order->order_number }}
            </div>

            @if($earnedPoints > 0)
                <div class="order-points-earned">
                    <div class="points-earned-label">{{ __('checkout.points_earned') }}</div>
                    <div class="points-earned-value">+{{ number_format($earnedPoints) }} {{ __('general.points') }}</div>
                </div>
            @endif

            <p class="order-success-message">
                {{ __('checkout.payment_details') }}
            </p>

            <div class="order-success-actions">
                <a href="{{ route('orders') }}" class="btn btn-secondary">{{ __('checkout.view_orders') }}</a>
                <a href="{{ route('home') }}" class="btn">{{ __('checkout.back_home') }}</a>
            </div>
        </div>
    </div>
</div>
