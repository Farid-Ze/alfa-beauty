<div>
    <!-- Page Hero with Breadcrumb -->
    <section class="page-hero" style="text-align: left;">
        <nav class="breadcrumb">
            <a href="{{ route('home') }}">{{ __('nav.home') }}</a>
            <span>›</span>
            <a href="{{ route('products.index') }}">{{ __('nav.products') }}</a>
            <span>›</span>
            <span style="color: var(--white);">{{ $product->name }}</span>
        </nav>
    </section>

    <!-- Product Detail Section -->
    <section class="product-detail-grid">
        
        <!-- Image Column (55%) -->
        <div class="product-gallery">
            <div class="product-main-image">
                @if($product->images && count($product->images) > 0)
                    <img src="{{ url('storage/' . $product->images[0]) }}" alt="{{ $product->name }}">
                @else
                    <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: var(--gray-400);">
                        <svg width="80" height="80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                @endif
                
                <!-- Badges -->
                @if($product->is_featured)
                    <div style="position: absolute; top: var(--space-md); left: var(--space-md);">
                        <span style="background: var(--black); color: var(--white); padding: 8px 16px; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.1em; font-weight: 500;">{{ __('products.best_seller') }}</span>
                    </div>
                @endif
            </div>

            <!-- Thumbnails -->
            @if(is_array($product->images) && count($product->images) > 1)
                <div class="product-thumbnails">
                    @foreach($product->images as $index => $image)
                        <div class="product-thumbnail {{ $index === 0 ? 'active' : '' }}">
                            <img src="{{ url('storage/' . $image) }}" alt="{{ $product->name }}">
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Details Column (45%) -->
        <div class="product-info-column">
            
            <!-- Brand & Title -->
            <div class="product-header">
                <span class="product-brand-name">
                    {{ $product->brand->name ?? 'Brand' }}
                </span>
                <h1 class="product-title">
                    {{ $product->name }}
                </h1>
                <!-- Product Meta (SKU + BPOM + Distributor) -->
                <p class="product-meta-line">
                    <span>SKU: {{ $product->sku }}</span>
                    <span class="meta-divider">•</span>
                    <span>BPOM: {{ $product->bpom_number ?? 'NA18201200123' }}</span>
                    <span class="meta-divider">•</span>
                    <span>{{ __('nav.official_distributor') }}</span>
                </p>
            </div>

            <!-- Pricing & Stock -->
            <div class="pricing-row">
                <div class="pricing-group">
                    <span class="price-main">Rp {{ number_format($product->price, 0, ',', '.') }}</span>
                    <span class="price-unit">/unit</span>
                </div>
                <div class="stock-indicator">
                    @if($product->in_stock)
                        <span class="stock-dot in-stock"></span>
                        <span class="stock-text in-stock">{{ __('general.in_stock') }}</span>
                    @else
                        <span class="stock-dot out-of-stock"></span>
                        <span class="stock-text out-of-stock">{{ __('general.out_of_stock') }}</span>
                    @endif
                </div>
            </div>

            <!-- Quantity & Subtotal Section -->
            <div 
                class="quantity-section"
                x-data="{ 
                    qty: 1, 
                    unitPrice: {{ $product->price }},
                    basePoints: {{ $product->points }},
                    get subtotal() { return this.qty * this.unitPrice },
                    get totalPoints() { return this.qty * this.basePoints },
                    formatPrice(num) {
                        return new Intl.NumberFormat('id-ID').format(num);
                    }
                }"
                x-init="$watch('qty', value => $dispatch('qty-changed', { qty: value, subtotal: qty * unitPrice, points: qty * basePoints }))"
            >
                <!-- Quantity Control -->
                <div class="quantity-row">
                    <label class="quantity-label">{{ __('products.quantity') }}</label>
                    <div class="quantity-controls">
                        <button @click="qty > 1 ? qty-- : null" class="quantity-btn">−</button>
                        <input type="text" x-model="qty" class="quantity-input" readonly>
                        <button @click="qty++" class="quantity-btn">+</button>
                    </div>
                </div>

                <!-- Dynamic Subtotal -->
                <div class="subtotal-row">
                    <span class="subtotal-label">{{ __('cart.subtotal') }}</span>
                    <div class="subtotal-value">
                        <span class="subtotal-price" x-text="'Rp ' + formatPrice(subtotal)"></span>
                        <span class="subtotal-points" x-text="'+' + totalPoints + ' Poin'"></span>
                    </div>
                </div>

                <!-- Full Width Add to Cart Button -->
                <button 
                    @click="$wire.addToCart(qty)"
                    wire:loading.attr="disabled"
                    class="btn add-to-cart-btn"
                >
                    <span wire:loading.remove wire:target="addToCart">{{ __('products.add_to_cart') }}</span>
                    <span wire:loading wire:target="addToCart">{{ __('general.loading') }}</span>
                </button>
            </div>



            <!-- Product Specs Link (B2B Style) -->
            <div class="product-specs-link" style="border-top: 1px solid var(--gray-200); padding-top: var(--space-lg);">
                <a href="#" class="specs-download-btn">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>{{ __('products.download_specs') }}</span>
                    <span class="specs-format">PDF</span>
                </a>
                <p class="specs-note">{{ __('products.specs_note') }}</p>
            </div>

        </div> <!-- End Info Column -->
    </section> <!-- End Grid -->

    <!-- Mobile Sticky Add-to-Cart Bar -->
    <div 
        class="pdp-sticky-cta"
        x-data="{ 
            qty: 1, 
            unitPrice: {{ $product->price }},
            basePoints: {{ $product->points }},
            get subtotal() { return this.qty * this.unitPrice },
            formatPrice(num) {
                return new Intl.NumberFormat('id-ID').format(num);
            }
        }"
        @qty-changed.window="qty = $event.detail.qty"
    >
        <div class="sticky-cta-inner">
            <div class="sticky-cta-price">
                <span class="sticky-price" x-text="'Rp ' + formatPrice(subtotal)"></span>
                <span class="sticky-points" x-text="qty > 1 ? qty + ' unit' : ''"></span>
            </div>
            <button 
                @click="$wire.addToCart(qty)"
                wire:loading.attr="disabled"
                class="btn sticky-cta-btn"
            >
                <span wire:loading.remove wire:target="addToCart">{{ __('products.add_to_cart') }}</span>
                <span wire:loading wire:target="addToCart">{{ __('general.loading') }}</span>
            </button>
        </div>
    </div>
</div>
