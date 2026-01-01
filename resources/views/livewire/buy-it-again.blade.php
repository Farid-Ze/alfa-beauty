<div>
    @if($products->isNotEmpty())
        <section class="buy-it-again-section">
            <div class="section-header">
                <h2 class="section-title">{{ __('orders.buy_again') }}</h2>
                <p class="section-subtitle">{{ __('orders.buy_again_desc') }}</p>
            </div>
            
            <div class="buy-again-grid">
                @foreach($products as $product)
                    @php
                        $productImages = is_array($product->images) ? $product->images : [];
                        $priceInfo = $prices[$product->id] ?? null;
                        $displayPrice = $priceInfo['price'] ?? $product->base_price;
                        $originalPrice = $priceInfo['original_price'] ?? $product->base_price;
                        $hasDiscount = $priceInfo && $priceInfo['price'] < $originalPrice;
                        $priceSource = $priceInfo['source'] ?? 'base_price';
                        $discountPercent = $hasDiscount ? round((1 - $displayPrice / $originalPrice) * 100) : 0;
                    @endphp
                    <div class="buy-again-card">
                        <a href="{{ route('products.show', $product->slug) }}" class="buy-again-image">
                            @if(count($productImages) > 0)
                                <img src="{{ url($productImages[0]) }}" alt="{{ $product->name }}">
                            @else
                                <div class="placeholder-image">
                                    <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                            @endif
                            
                            <!-- B2B Discount Badge -->
                            @if($hasDiscount)
                                <span class="cart-item-badge">-{{ $discountPercent }}%</span>
                            @endif
                        </a>
                        
                        <div class="buy-again-info">
                            <span class="buy-again-brand">{{ $product->brand->name ?? 'Brand' }}</span>
                            <h3 class="buy-again-name">
                                <a href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a>
                            </h3>
                            <div class="buy-again-price">
                                @if($hasDiscount)
                                    <span class="price-original">Rp {{ number_format($originalPrice, 0, ',', '.') }}</span>
                                @endif
                                <span class="price-current {{ $hasDiscount ? 'price-discounted' : '' }}">
                                    Rp {{ number_format($displayPrice, 0, ',', '.') }}
                                </span>
                                @if($priceSource === 'customer_price_list')
                                    <span class="price-source-tag">Harga Khusus</span>
                                @elseif($priceSource === 'volume_tier')
                                    <span class="price-source-tag">Diskon Volume</span>
                                @elseif($priceSource === 'loyalty_tier')
                                    <span class="price-source-tag">Diskon Loyalty</span>
                                @endif
                            </div>
                        </div>
                        
                        <button 
                            wire:click="addToCart({{ $product->id }})"
                            wire:loading.attr="disabled"
                            class="buy-again-btn"
                        >
                            <span wire:loading.remove wire:target="addToCart({{ $product->id }})">+ {{ __('orders.buy_again') }}</span>
                            <span wire:loading wire:target="addToCart({{ $product->id }})">{{ __('general.loading') }}...</span>
                        </button>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
