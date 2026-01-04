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
                                        <img src="{{ isset($item->product->images[0]) ? url($item->product->images[0]) : asset('images/product-color.png') }}" alt="{{ $item->product->name }}">
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

            {{-- Pagination --}}
            @if($orders->hasPages())
                <div class="pagination-wrapper" style="margin-top: 2rem; display: flex; justify-content: space-between; align-items: center;">
                    <span class="pagination-info" style="color: var(--gray-500); font-size: 0.875rem;">
                        Menampilkan {{ $orders->firstItem() }} - {{ $orders->lastItem() }} dari {{ $orders->total() }} pesanan
                    </span>
                    <div class="pagination-nav" style="display: flex; gap: 0.5rem;">
                        @if($orders->onFirstPage())
                            <span class="pagination-btn pagination-btn-disabled" style="padding: 0.5rem 1rem; border: 1px solid var(--gray-200); border-radius: var(--radius-sm); color: var(--gray-300); cursor: not-allowed;">← Sebelumnya</span>
                        @else
                            <a href="{{ $orders->previousPageUrl() }}" class="pagination-btn" style="padding: 0.5rem 1rem; border: 1px solid var(--gray-200); border-radius: var(--radius-sm); color: var(--black); text-decoration: none; transition: background 0.2s;" onmouseover="this.style.background='var(--gray-100)'" onmouseout="this.style.background='transparent'">← Sebelumnya</a>
                        @endif
                        
                        @if($orders->hasMorePages())
                            <a href="{{ $orders->nextPageUrl() }}" class="pagination-btn" style="padding: 0.5rem 1rem; border: 1px solid var(--gray-200); border-radius: var(--radius-sm); color: var(--black); text-decoration: none; transition: background 0.2s;" onmouseover="this.style.background='var(--gray-100)'" onmouseout="this.style.background='transparent'">Selanjutnya →</a>
                        @else
                            <span class="pagination-btn pagination-btn-disabled" style="padding: 0.5rem 1rem; border: 1px solid var(--gray-200); border-radius: var(--radius-sm); color: var(--gray-300); cursor: not-allowed;">Selanjutnya →</span>
                        @endif
                    </div>
                </div>
            @endif
        @endif
    </div>

    <!-- Buy It Again Section -->
    @livewire('buy-it-again')
</div>


