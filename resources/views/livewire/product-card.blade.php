@php
    // Use B2B price info if provided, otherwise fall back to product defaults
    $displayPrice = $priceInfo['price'] ?? $product->price;
    $originalPrice = $priceInfo['original_price'] ?? $product->base_price;
    $priceSource = $priceInfo['source'] ?? 'base_price';
    $hasDiscount = $priceInfo && isset($priceInfo['price']) && $priceInfo['price'] < $originalPrice;
    $discountPercent = $hasDiscount ? round((1 - $displayPrice / $originalPrice) * 100) : 0;
    $hasVolumePricing = $product->has_volume_pricing || ($priceSource === 'volume_tier');
    $isOutOfStock = !$product->in_stock;
@endphp
<article class="product-card {{ $isOutOfStock ? 'product-out-of-stock' : '' }}">
    <a href="{{ route('products.show', $product->slug) }}" class="product-image">
        <img src="{{ isset($product->images[0]) ? url($product->images[0]) : asset('images/product-color.png') }}" 
             alt="{{ $product->name }}" 
             loading="lazy"
             width="280"
             height="280"
             decoding="async">
        
        {{-- Out of Stock Badge (highest priority) --}}
        @if($isOutOfStock)
            <span class="product-badge product-badge-oos">HABIS</span>
        {{-- B2B Discount Badge --}}
        @elseif($hasDiscount)
            <span class="product-badge product-badge-discount">-{{ $discountPercent }}%</span>
        @elseif($hasVolumePricing)
            <span class="product-badge product-badge-volume">Volume</span>
        @endif
    </a>
    <div class="product-details">
        <span class="product-brand">{{ $product->brand->name ?? __('products.brand') }}</span>
        <h3 class="product-name">
            <a href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a>
        </h3>
        <div class="product-pricing">
            @if($hasDiscount)
                <span class="price-original" style="text-decoration: line-through; color: var(--neutral-500); font-size: 0.75rem;">
                    Rp {{ number_format($originalPrice, 0, ',', '.') }}
                </span>
            @endif
            <span class="price-current {{ $hasDiscount ? 'price-discounted' : '' }}">
                Rp {{ number_format($displayPrice, 0, ',', '.') }}
            </span>
            @if($priceSource === 'customer_price_list')
                <span class="price-source-tag">{{ __('products.special_price') }}</span>
            @elseif($priceSource === 'volume_tier')
                <span class="price-source-tag">{{ __('products.volume_discounts') }}</span>
            @elseif($priceSource === 'loyalty_tier')
                <span class="price-source-tag">{{ __('products.loyalty_discount') }}</span>
            @elseif($hasVolumePricing)
                <span class="price-source-tag">{{ __('products.view_details') }}</span>
            @else
                <span class="price-points">+{{ $product->points }} {{ __('general.pts') }}</span>
            @endif
        </div>
    </div>
    @if($isOutOfStock)
        <button class="btn-quick-add btn-quick-add-disabled" disabled>
            <span>{{ __('products.out_of_stock') }}</span>
        </button>
    @else
        <button class="btn-quick-add" wire:click="addToCart" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="addToCart">+ {{ __('products.quick_add') }}</span>
            <span wire:loading wire:target="addToCart">{{ __('general.loading') }}</span>
        </button>
    @endif
</article>
