<div>
    @if($products->isNotEmpty())
        <section class="buy-it-again-section">
            <div class="section-header">
                <h2 class="section-title">Buy It Again</h2>
                <p class="section-subtitle">Quick reorder from your purchase history</p>
            </div>
            
            <div class="buy-again-grid">
                @foreach($products as $product)
                    @php
                        $productImages = is_array($product->images) ? $product->images : [];
                    @endphp
                    <div class="buy-again-card">
                        <a href="{{ route('products.show', $product->slug) }}" class="buy-again-image">
                            @if(count($productImages) > 0)
                                <img src="{{ url('storage/' . $productImages[0]) }}" alt="{{ $product->name }}">
                            @else
                                <div class="placeholder-image">
                                    <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                            @endif
                            
                            <!-- B2B Indicator (if user has B2B pricing) -->
                            @auth
                                @if(Auth::user()->has_b2b_pricing)
                                    <span class="cart-item-badge">B2B</span>
                                @endif
                            @endauth
                        </a>
                        
                        <div class="buy-again-info">
                            <span class="buy-again-brand">{{ $product->brand->name ?? 'Brand' }}</span>
                            <h3 class="buy-again-name">
                                <a href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a>
                            </h3>
                            <p class="buy-again-price">
                                Rp {{ number_format($product->base_price, 0, ',', '.') }}
                                @auth
                                    @if(Auth::user()->has_b2b_pricing)
                                        <span class="price-source-tag">Lihat Harga Khusus</span>
                                    @endif
                                @endauth
                            </p>
                        </div>
                        
                        <button 
                            wire:click="addToCart({{ $product->id }})"
                            wire:loading.attr="disabled"
                            class="buy-again-btn"
                        >
                            <span wire:loading.remove wire:target="addToCart({{ $product->id }})">+ Reorder</span>
                            <span wire:loading wire:target="addToCart({{ $product->id }})">Adding...</span>
                        </button>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
