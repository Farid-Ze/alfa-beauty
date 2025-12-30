<article class="product-card">
    <a href="{{ route('products.show', $product->slug) }}" class="product-image">
        <img src="{{ isset($product->images[0]) ? url('storage/' . $product->images[0]) : asset('images/product-color.png') }}" alt="{{ $product->name }}">
    </a>
    <div class="product-details">
        <span class="product-brand">{{ $product->brand->name ?? __('products.brand') }}</span>
        <h3 class="product-name">
            <a href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a>
        </h3>
        <div class="product-pricing">
            <span class="price-current">Rp {{ number_format($product->price, 0, ',', '.') }}</span>
            <span class="price-points">+{{ $product->points }} {{ __('general.pts') }}</span>
        </div>
    </div>
    <button class="btn-quick-add" wire:click="addToCart" wire:loading.attr="disabled">
        <span wire:loading.remove wire:target="addToCart">+ {{ __('products.quick_add') }}</span>
        <span wire:loading wire:target="addToCart">{{ __('general.loading') }}</span>
    </button>
</article>

