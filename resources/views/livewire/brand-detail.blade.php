<div>
    <!-- Bold Editorial Hero -->
    <section class="brand-editorial-hero">
    <!-- Top Bar: Breadcrumb Only -->
        <div class="editorial-top">
            <nav class="breadcrumb">
                <a href="{{ route('home') }}">{{ __('nav.home') }}</a>
                <span>›</span>
                <a href="{{ route('products.index') }}">{{ __('nav.products') }}</a>
                <span>›</span>
                <span class="breadcrumb-current">{{ $brand->name }}</span>
            </nav>
        </div>

        <!-- Main Hero Content -->
        <div class="editorial-hero-main">
            <!-- Left: Brand Name + Tagline + CTA -->
            <div class="editorial-left-panel">
                <div class="editorial-brand-name">
                    <h1 class="brand-name-primary">{{ strtoupper(explode(' ', $brand->name)[0]) }}</h1>
                    <span class="brand-name-secondary">{{ implode(' ', array_slice(explode(' ', $brand->name), 1)) ?: 'Professional' }}</span>
                </div>
                <p class="editorial-tagline">
                    {{ __('brand.professional_hair_care') }}<br>
                    {{ __('brand.from') }} <em>{{ $brand->origin_country }}</em>
                </p>
                <a href="{{ route('products.index', ['selectedBrands' => [$brand->id]]) }}" class="editorial-explore-btn">
                    {{ __('brand.explore_products') }} →
                </a>
            </div>

            <!-- Center: Featured Product (Full Height) -->
            <div class="editorial-product-center">
                @if($featuredProduct)
                <a href="{{ route('products.show', $featuredProduct->slug) }}" class="editorial-product-wrapper">
                    <div class="editorial-product-image">
                        @if($featuredProduct->images && count($featuredProduct->images) > 0)
                            <img src="{{ url($featuredProduct->images[0]) }}" alt="{{ $featuredProduct->name }}">
                        @else
                            <div class="editorial-product-placeholder">
                                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="0.5">
                                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                                    <circle cx="8.5" cy="8.5" r="1.5"/>
                                    <path d="M21 15l-5-5L5 21"/>
                                </svg>
                            </div>
                        @endif
                    </div>
                </a>
                @endif
            </div>

            <!-- Right: Metrics + CTA -->
            <div class="editorial-right-panel">
                <div class="editorial-metrics">
                    <div class="metric-block">
                        <span class="metric-index">01</span>
                        <span class="metric-label">{{ __('brand.products') }}</span>
                        <span class="metric-value">{{ $productCount }}</span>
                    </div>
                    <div class="metric-block">
                        <span class="metric-index">02</span>
                        <span class="metric-label">{{ __('brand.available') }}</span>
                        <span class="metric-value">{{ number_format($totalStock) }}</span>
                    </div>
                    <div class="metric-block">
                        <span class="metric-index">03</span>
                        <span class="metric-label">{{ __('brand.origin') }}</span>
                        <span class="metric-value">{{ $brand->origin_country }}</span>
                    </div>
                </div>

                <div class="editorial-cta-stack">
                    @if($featuredProduct)
                    <a href="{{ route('products.show', $featuredProduct->slug) }}" class="editorial-btn-primary">
                        {{ __('brand.view_this_product') }}
                    </a>
                    @endif
                    <a href="{{ route('register') }}" class="editorial-btn-outline">
                        {{ __('brand.become_partner') }}
                    </a>
                </div>
            </div>
        </div>
    </section>


    <!-- Brand Navigator -->
    @if($otherBrands->count() > 0)
    <section class="brand-navigator">
        <span class="nav-label">{{ __('brand.explore_other_brands') }}</span>
        <div class="nav-brands">
            @foreach($otherBrands as $other)
            <a href="{{ route('brands.show', $other->slug) }}" class="nav-brand-item">
                @if($other->logo_url)
                    <div class="nav-brand-logo">
                        <img src="{{ url($other->logo_url) }}" alt="{{ $other->name }}">
                    </div>
                @else
                    <span class="nav-brand-name">{{ $other->name }}</span>
                @endif
                <span class="nav-brand-origin">{{ $other->origin_country }}</span>
            </a>
            @endforeach
        </div>
    </section>
    @endif
</div>

