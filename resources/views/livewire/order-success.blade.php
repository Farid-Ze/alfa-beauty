<div>
    <!-- Page Hero -->
    <section class="page-hero">
        <div style="color: var(--green); width: 80px; height: 80px; margin: 0 auto var(--space-md);">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 100%; height: 100%;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <h1>{{ __('checkout.order_success') }}</h1>
        <p>{{ __('checkout.thank_you') }}</p>
    </section>

    <div class="orders-container" style="max-width: 600px; text-align: center;">
        <div class="order-card" style="padding: var(--space-xl);">
            <div style="background: var(--gray-100); padding: 12px 24px; border-radius: 6px; font-family: monospace; font-weight: 600; margin-bottom: var(--space-lg); display: inline-block; letter-spacing: 1px;">
                {{ __('checkout.order_number') }} #{{ $order->order_number }}
            </div>

            @if($earnedPoints > 0)
                <div style="background: linear-gradient(135deg, #C9A962 0%, #E5D9A8 100%); color: #1a1a1a; padding: 16px 24px; border-radius: 8px; margin-bottom: var(--space-lg);">
                    <div style="font-size: 0.875rem; opacity: 0.8; margin-bottom: 4px;">{{ __('checkout.points_earned') }}</div>
                    <div style="font-size: 1.5rem; font-weight: 700;">+{{ number_format($earnedPoints) }} {{ __('general.points') }}</div>
                </div>
            @endif

            <p style="color: var(--gray-600); margin-bottom: var(--space-xl); line-height: 1.6;">
                {{ __('checkout.payment_details') }}
            </p>

            <div style="display: flex; gap: var(--space-md); justify-content: center;">
                <a href="{{ route('orders') }}" class="btn" style="background: var(--gray-100); color: var(--black);">{{ __('checkout.view_orders') }}</a>
                <a href="{{ route('home') }}" class="btn">{{ __('checkout.back_home') }}</a>
            </div>
        </div>
    </div>
</div>

