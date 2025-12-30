<div>
    <!-- Hero Banner -->
    <section class="hero">
        <div class="hero-bg">
            <img src="{{ asset('images/hero-salon.png') }}" alt="Alfa Beauty Professional Salon" class="hero-image">
        </div>
        <div class="hero-content">
            <h1 class="hero-title">
                {{ __('home.hero_title_1') }}<br>
                <em>{{ __('home.hero_title_2') }}</em>
            </h1>
            <p class="hero-tagline">{{ __('home.hero_tagline') }}</p>
        </div>
        <!-- Scroll Indicator - V3 Spec: No CTA in hero for premium feel -->
        <div class="hero-scroll">
            <span class="scroll-indicator">{{ __('home.scroll') }}</span>
        </div>
    </section>

    <!-- Products Section -->
    <section class="products" id="products">
        <!-- Header with Terminal Area CTA -->
        <div class="section-header">
            <div>
                <h2 class="section-title">{{ __('home.best_sellers') }}</h2>
                <p class="section-subtitle">{{ __('home.curated_results') }}</p>
            </div>
            <a href="{{ route('products.index') }}" class="btn">{{ __('general.view_all') }} →</a>
        </div>

        <livewire:product-list />
    </section>

    <!-- Brands Section - Premium Asymmetric Design -->
    <section class="brands" id="brands">
        <div class="brands-header">
            <h2 class="section-title">{{ __('home.our_brands') }}</h2>
        </div>
        <div class="brands-list">
            @foreach($brands as $index => $brand)
            <a href="{{ route('brands.show', $brand->slug) }}" class="brand-item" data-index="{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}">
                <span class="brand-name">{{ $brand->name }}</span>
                <span class="brand-meta">{{ $brand->origin_country }} · {{ $brand->is_own_brand ? __('home.our_brand') : __('home.partner') }}</span>
                <div class="brand-hover-info">
                    <div class="brand-hover-stat">
                        <span class="hover-stat-num">{{ $brand->product_count }}</span>
                        <span class="hover-stat-label">{{ __('home.products_count') }}</span>
                    </div>
                    <div class="brand-hover-stat">
                        <span class="hover-stat-num">{{ number_format($brand->total_stock) }}</span>
                        <span class="hover-stat-label">{{ __('home.available') }}</span>
                    </div>
                    <div class="brand-hover-stat">
                        <span class="hover-stat-num">{{ $brand->origin_country }}</span>
                        <span class="hover-stat-label">{{ __('home.origin') }}</span>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </section>

    <!-- Company Profile -->
    <section class="company" id="about">
        <div class="company-visual">
            <img src="{{ asset('images/brands-interior.png') }}" alt="Alfa Beauty Facility" class="company-image">
            
            <!-- Desktop Headline: Structural Split -->
            <div class="company-headline-desktop">
                <span class="text-white">Partner</span>
                <span class="text-black">Terpercaya</span>
            </div>

            <!-- Mobile Headline: Simple Stack -->
            <h2 class="company-headline-mobile">{{ __('home.trusted_partner') }}</h2>
        </div>
        <div class="company-details">
            <p class="company-desc">{{ __('home.company_desc') }}</p>
            <div class="company-stats">
                <div class="stat"><span class="stat-num">15+</span><span class="stat-txt">{{ __('home.years') }}</span></div>
                <div class="stat"><span class="stat-num">3,000+</span><span class="stat-txt">{{ __('home.salons') }}</span></div>
                <div class="stat"><span class="stat-num">50+</span><span class="stat-txt">{{ __('home.cities') }}</span></div>
            </div>
        </div>
    </section>

    <!-- Partner Testimonials -->
    <section class="testimonials-section" 
        x-data="{ 
            active: 0,
            paused: false,
            interval: null,
            testimonials: [
                { quote: 'Kualitas produk konsisten, pengiriman cepat. Quick Order sangat membantu kesibukan salon saya.', name: 'Siti Rahayu', salon: 'Salon Cantik Bunda, Surabaya', tier: 'Gold', since: '2019', image: '{{ asset('images/testimonial-siti.png') }}' },
                { quote: 'Harga kompetitif untuk produk premium. Support tim sangat responsif lewat WhatsApp.', name: 'Budi Santoso', salon: 'Urban Cuts Barbershop, Jakarta', tier: 'Silver', since: '2021', image: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=80&h=80&fit=crop&crop=face' },
                { quote: 'Platform paling lengkap untuk kebutuhan salon profesional. Loyalty points sangat menguntungkan.', name: 'Maya Putri', salon: 'Glow Beauty Studio, Bandung', tier: 'Gold', since: '2020', image: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=80&h=80&fit=crop&crop=face' }
            ],
            next() { this.active = (this.active + 1) % this.testimonials.length },
            prev() { this.active = (this.active - 1 + this.testimonials.length) % this.testimonials.length },
            goTo(index) { this.active = index },
            startAutoplay() {
                this.interval = setInterval(() => { if (!this.paused) this.next() }, 5000)
            },
            stopAutoplay() {
                if (this.interval) clearInterval(this.interval)
            }
        }"
        x-init="startAutoplay()"
        x-on:mouseenter="paused = true"
        x-on:mouseleave="paused = false"
        @touchstart.passive="touchStart = $event.touches[0].clientX"
        @touchend.passive="
            const diff = $event.changedTouches[0].clientX - touchStart;
            if (Math.abs(diff) > 50) diff > 0 ? prev() : next()
        "
    >
        <div class="testimonials-container">
            <!-- Section Header -->
            <div class="testimonials-header">
                <h2 class="section-title">{{ __('home.partner_stories') }}</h2>
            </div>

            <!-- Carousel -->
            <div class="testimonials-carousel">
                <!-- Quote Mark -->
                <div class="testimonials-quote-mark">"</div>

                <!-- Cards Stack -->
                <div class="testimonials-stack">
                    <template x-for="(t, index) in testimonials" :key="index">
                        <div 
                            class="testimonial-card"
                            :class="{ 
                                'active': active === index,
                                'prev': active === (index + 1) % testimonials.length,
                                'next': active === (index - 1 + testimonials.length) % testimonials.length
                            }"
                            @click="active = index"
                        >
                            <blockquote x-text="t.quote"></blockquote>
                            <div class="testimonial-author">
                                <img :src="t.image" :alt="t.name">
                                <div class="author-info">
                                    <strong x-text="t.name"></strong>
                                    <span x-text="t.salon"></span>
                                    <span class="tier-badge" :class="t.tier.toLowerCase()">
                                        <span x-text="t.tier"></span> · {{ __('home.since') }} <span x-text="t.since"></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Controls: Dots (left) + Arrows (right) -->
                <div class="testimonials-controls">
                    <div class="testimonials-nav">
                        <template x-for="(t, index) in testimonials" :key="'dot-'+index">
                            <button 
                                class="nav-dot" 
                                :class="{ 'active': active === index }"
                                @click="active = index"
                            ></button>
                        </template>
                    </div>
                    <div class="testimonials-arrows">
                        <button class="testimonials-arrow" @click="prev()">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>
                        <button class="testimonials-arrow" @click="next()">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section - Elegant Minimalist -->
    <section class="cta-elegant">
        <div class="cta-container">
            <h2 class="cta-headline">
                <span>Mulai</span>
                <em>Bermitra</em>
            </h2>
            <p class="cta-steps">
                <span>{{ __('home.register_step') }}</span>
                <span class="cta-dot">·</span>
                <span>{{ __('home.verify_step') }}</span>
                <span class="cta-dot">·</span>
                <span>{{ __('home.wholesale_access') }}</span>
            </p>
            <a href="/register" class="cta-button">{{ __('home.register_now') }}</a>
        </div>
    </section>
</div>

