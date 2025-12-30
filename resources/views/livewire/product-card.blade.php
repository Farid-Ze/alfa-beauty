<article class="product-card">
    <a href="{{ route('products.show', $product->slug) }}" class="product-image">
        <img src="{{ isset($product->images[0]) ? url('storage/' . $product->images[0]) : asset('images/product-color.png') }}" alt="{{ $product->name }}">
        
        <!-- B2B/Volume Pricing Indicator -->
        @auth
            @if(Auth::user()->has_b2b_pricing || $product->has_volume_pricing)
                <span class="cart-item-badge" style="position: absolute; top: 8px; right: 8px;">B2B</span>
            @endif
        @else
            @if($product->has_volume_pricing)
                <span class="cart-item-badge" style="position: absolute; top: 8px; right: 8px;">Volume</span>
            @endif
        @endauth
    </a>
    <div class="product-details">
        <span class="product-brand">{{ $product->brand->name ?? __('products.brand') }}</span>
        <h3 class="product-name">
            <a href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a>
        </h3>
        <div class="product-pricing">
            <span class="price-current">Rp {{ number_format($product->price, 0, ',', '.') }}</span>
            @auth
                @if(Auth::user()->has_b2b_pricing || $product->has_volume_pricing)
                    <span class="price-source-tag">Lihat Detail</span>
                @else
                    <span class="price-points">+{{ $product->points }} {{ __('general.pts') }}</span>
                @endif
            @else
                <span class="price-points">+{{ $product->points }} {{ __('general.pts') }}</span>
            @endauth
        </div>
    </div>
    <button class="btn-quick-add" wire:click="addToCart" wire:loading.attr="disabled">
        <span wire:loading.remove wire:target="addToCart">+ {{ __('products.quick_add') }}</span>
        <span wire:loading wire:target="addToCart">{{ __('general.loading') }}</span>
    </button>
</article>
