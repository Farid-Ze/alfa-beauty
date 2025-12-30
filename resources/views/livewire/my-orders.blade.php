<div>
    <!-- Page Hero -->
    <section class="page-hero">
        <h1>{{ __('orders.my_orders') }}</h1>
        <p class="page-hero-subtitle">{{ __('orders.my_orders_subtitle') }}</p>
    </section>

    <div class="orders-container">
        @if($orders->isEmpty())
            <div class="order-card empty-state">
                <p class="empty-state-text">{{ __('orders.no_orders_desc') }}</p>
                <a href="{{ route('products.index') }}" class="btn">{{ __('orders.start_shopping') }}</a>
            </div>
        @else
            <div class="orders-list">
                @foreach($orders as $order)
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <span class="order-number">{{ $order->order_number }}</span>
                                <span class="order-date">{{ $order->created_at->format('d M Y, H:i') }}</span>
                            </div>
                            <div class="order-badges">
                                <span class="order-status-badge order-status-badge--{{ $order->status }}">
                                    {{ __('orders.' . $order->status) }}
                                </span>
                                
                                @php
                                    $pointsEarned = $order->pointTransactions->where('type', 'earn')->sum('amount');
                                @endphp
                                @if($pointsEarned > 0)
                                    <span class="order-points-badge">
                                        +{{ number_format($pointsEarned) }} {{ __('general.pts') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        
                        <div class="order-items">
                            @foreach($order->items as $item)
                                <div class="order-item">
                                    <div class="order-item-image">
                                        <img src="{{ isset($item->product->images[0]) ? url('storage/' . $item->product->images[0]) : asset('images/product-color.png') }}" alt="{{ $item->product->name }}">
                                    </div>
                                    <div class="order-item-info">
                                        <h4 class="order-item-name">{{ $item->product->name }}</h4>
                                        <p class="order-item-meta">{{ __('orders.qty') }}: {{ $item->quantity }} × Rp {{ number_format($item->unit_price, 0, ',', '.') }}</p>
                                    </div>
                                    <div class="order-item-total">
                                        <p class="order-item-price">Rp {{ number_format($item->total_price, 0, ',', '.') }}</p>
                                    </div>
                                </div>
                            @endforeach

                            <div class="order-footer">
                                <span class="order-footer-label">{{ __('checkout.total') }}</span>
                                <span class="order-footer-amount">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Buy It Again Section -->
    @livewire('buy-it-again')
</div>


