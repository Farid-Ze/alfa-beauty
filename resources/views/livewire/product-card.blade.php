@php
    // Use B2B price info if provided, otherwise fall back to product defaults
    $displayPrice = $priceInfo['price'] ?? $product->price;
    $originalPrice = $priceInfo['original_price'] ?? $product->base_price;
    $priceSource = $priceInfo['source'] ?? 'base_price';
    $hasDiscount = $priceInfo && isset($priceInfo['price']) && $priceInfo['price'] < $originalPrice;
    $discountPercent = $hasDiscount ? round((1 - $displayPrice / $originalPrice) * 100) : 0;
    $hasVolumePricing = $product->has_volume_pricing || ($priceSource === 'volume_tier');
@endphp
<article class="product-card">
    <a href="{{ route('products.show', $product->slug) }}" class="product-image">
        <img src="{{ isset($product->images[0]) ? url($product->images[0]) : asset('images/product-color.png') }}" 
             alt="{{ $product->name }}" 
             loading="lazy"
             width="280"
             height="280"
             decoding="async">
        
        <!-- B2B Discount Badge -->
        @if($hasDiscount)
            <span class="cart-item-badge" style="position: absolute; top: 8px; right: 8px;">-{{ $discountPercent }}%</span>
        @elseif($hasVolumePricing)
            <span class="cart-item-badge" style="position: absolute; top: 8px; right: 8px;">Volume</span>
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
    <button class="btn-quick-add" wire:click="addToCart" wire:loading.attr="disabled">
        <span wire:loading.remove wire:target="addToCart">+ {{ __('products.quick_add') }}</span>
        <span wire:loading wire:target="addToCart">{{ __('general.loading') }}</span>
    </button>
</article>
