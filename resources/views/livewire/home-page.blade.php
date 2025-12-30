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
            <p class="hero-tagline-bold">{{ __('home.hero_tagline_bold') }}</p>
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

    <!-- Our Brands Section - Original Layout with Logos -->
    <section class="brands" id="brands">
        <div class="brands-header">
            <h2 class="section-title">{{ __('home.our_brands') }}</h2>
        </div>
        <div class="brands-list">
            @foreach($brands as $index => $brand)
            <a href="{{ route('brands.show', $brand->slug) }}" class="brand-item" data-index="{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}">
                @if($brand->logo_url)
                    <div class="brand-logo-display">
                        <img src="{{ url($brand->logo_url) }}" alt="{{ $brand->name }}">
                    </div>
                @else
                    <span class="brand-name">{{ $brand->name }}</span>
                @endif
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
                <span class="text-white">{{ __('home.trusted_partner') }}</span>
                <span class="text-black">{{ __('home.partner_terpercaya') }}</span>
            </div>

            <!-- Mobile Headline: Simple Stack -->
            <h2 class="company-headline-mobile">{{ __('home.trusted_partner') }} {{ __('home.partner_terpercaya') }}</h2>
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

    <!-- Partner Testimonials - Clean Card Style -->
    <section class="testimonials-clean" 
        x-data="{ 
            active: 1,
            paused: false,
            interval: null,
            enableTransition: true,
            isAnimating: false,
            testimonials: [
                { 
                    quote: 'Beralih ke Alfa adalah keputusan logis murni. Kami memangkas limbah inventaris hingga 20% berkat katalog terkurasi mereka. Sangat masuk akal.',
                    company: 'SCB Signature Salon',
                    location: 'Jakarta',
                    initials: 'SC',
                    color: '#E91E63'
                },
                { 
                    quote: 'Struktur harganya memungkinkan kami menjaga margin sehat tanpa mengorbankan kualitas teknis. Pilihan logis untuk bisnis yang sedang berkembang.',
                    company: 'Urban Cuts Collective',
                    location: 'Surabaya',
                    initials: 'UC',
                    color: '#9C27B0'
                },
                { 
                    quote: 'Akhirnya, distributor yang paham bahwa Mendesak artinya Sekarang. Keandalannya tanpa cela.',
                    company: 'Glow Beauty Studio',
                    location: 'Bandung',
                    initials: 'GB',
                    color: '#673AB7'
                }
            ],
            items: [],
            originalLength: 0,
            touchStartX: 0,
            touchEndX: 0,
            swipeThreshold: 50,
            init() {
                this.originalLength = this.testimonials.length;
                // Triple duplicate for robust buffering using concat for safety
                this.items = this.testimonials.concat(this.testimonials, this.testimonials);
                
                // Start at beginning of Set 2
                this.active = this.originalLength;
                this.startAutoplay();
            },
            handleTouchStart(e) {
                this.touchStartX = e.changedTouches[0].screenX;
            },
            handleTouchEnd(e) {
                this.touchEndX = e.changedTouches[0].screenX;
                if (this.touchEndX < this.touchStartX - this.swipeThreshold) {
                    this.startAutoplay();
                    this.next();
                }
                if (this.touchEndX > this.touchStartX + this.swipeThreshold) {
                    this.startAutoplay();
                    this.prev();
                }
            },
            next(isManual = false) {
                if (isManual) this.startAutoplay();
                if (this.isAnimating) return;
                this.isAnimating = true;
                this.active++;
                
                // Logic: Allow animation to finish, then normalize position if needed
                setTimeout(() => {
                    this.isAnimating = false;
                    // If we are in Set 3 (Index >= 2 * L), jump back to Set 2
                    if (this.active >= this.originalLength * 2) {
                        this.enableTransition = false;
                        this.active = this.active - this.originalLength;
                        setTimeout(() => { this.enableTransition = true; }, 40);
                    }
                }, 500);
            },
            prev() {
                this.startAutoplay();
                if (this.isAnimating) return;
                this.isAnimating = true;
                this.active--;
                
                // Logic: Allow animation to finish, then normalize position if needed
                setTimeout(() => {
                    this.isAnimating = false;
                    // If we are in Set 1 (Index < L), jump forward to Set 2
                    if (this.active < this.originalLength) {
                        this.enableTransition = false;
                        this.active = this.active + this.originalLength;
                        setTimeout(() => { this.enableTransition = true; }, 40);
                    }
                }, 500);
            },
            stopAutoplay() {
                if (this.interval) clearInterval(this.interval);
            },
            startAutoplay() {
                if (this.interval) clearInterval(this.interval);
                this.interval = setInterval(() => { this.next() }, 5000);
            }
        }"
        x-init="$nextTick(() => init())"
        @mouseenter="stopAutoplay()"
        @mouseleave="startAutoplay()"
        @touchstart="stopAutoplay()"
        @touchend="startAutoplay()"
    >
        <div class="testimonials-clean-container">
            <!-- Cards Carousel -->
            <div class="testimonials-clean-track" 
                 @touchstart="handleTouchStart($event)"
                 @touchend="handleTouchEnd($event)"
                 :style="'transform: translateX(calc(50vw - (' + active + ' * (var(--card-width) + var(--card-gap))) - (var(--card-width) / 2))); transition: ' + (enableTransition ? 'transform 0.5s cubic-bezier(0.25, 1, 0.5, 1)' : 'none')">
                <template x-for="(t, index) in items" :key="index">
                    <div 
                        class="testimonial-clean-card"
                        :class="{ 
                            'active': active === index,
                            'adjacent': index === active - 1 || index === active + 1
                        }"
                        :style="'transition: ' + (enableTransition ? 'opacity 0.5s ease-out, filter 0.5s ease-out' : 'none')"
                        @click="active = index"
                    >
                        <div class="testimonials__item">
                            <div class="testimonials__quote text-start">
                                <!-- Rating Star -->
                                <div class="rating-star mb-6" style="color: var(--gold); font-size: 16px; letter-spacing: 2px; margin-bottom: 24px;">
                                    ★★★★★
                                </div>
                                
                                <!-- Quote Text -->
                                <div class="testimonials__quote-text body-1" style="font-family: var(--font-body); font-size: 1.15rem; line-height: 1.6; color: var(--black); margin-bottom: 40px;">
                                    <blockquote x-text="t.quote" style="border:none; padding:0; margin:0;"></blockquote>
                                </div>
                                
                                <!-- Bio -->
                                <div class="testimonials__quote-bio flex flex-row items-center justify-start mt-10 gap-x-8" style="display: flex; align-items: center; gap: 32px; margin-top: 40px;">
                                    <!-- Avatar -->
                                    <div class="testimonials__quote-avatar">
                                        <div class="testimonial-avatar-container" :style="'background-color: ' + t.color">
                                            <span x-text="t.initials"></span>
                                        </div>
                                    </div>
                                    
                                    <!-- Info -->
                                    <div class="testimonials__quote-info text-left">
                                        <div class="testimonials__quote-name" x-text="t.company"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
            
            <!-- Navigation Dots -->
            <div class="testimonials-clean-nav">
                <button class="testimonials-clean-arrow" @click="prev()">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
                <div class="testimonials-clean-dots">
                    <template x-for="(t, index) in testimonials" :key="'dot-'+index">
                        <button 
                            class="testimonial-dot" 
                            :class="{ 'active': (active % originalLength) === index }"
                            @click="active = index + originalLength"
                        ></button>
                    </template>
                </div>
                <button class="testimonials-clean-arrow" @click="next()">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </div>
    </section>

    <!-- CTA Section - Get in Touch (matches footer background) -->
    <section class="cta-contact">
        <div class="cta-contact-container">
            <h2 class="cta-contact-title">{{ __('home.get_in_touch') }}</h2>
            <p class="cta-contact-subtitle">{{ __('home.contact_subtitle') }}</p>
            
            <form class="cta-contact-form" action="#" method="POST">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label for="contact-name">{{ __('checkout.name') }}</label>
                        <input type="text" id="contact-name" name="name" placeholder="{{ __('checkout.name') }}">
                    </div>
                    <div class="form-group">
                        <label for="contact-email">EMAIL *</label>
                        <input type="email" id="contact-email" name="email" placeholder="Email" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="contact-subject">SUBJECT</label>
                    <input type="text" id="contact-subject" name="subject" placeholder="Subject">
                </div>
                
                <div class="form-group">
                    <label for="contact-phone">{{ __('checkout.phone') }}</label>
                    <input type="tel" id="contact-phone" name="phone" placeholder="{{ __('checkout.phone') }}">
                </div>
                
                <div class="form-group">
                    <label for="contact-message">COMMENT</label>
                    <textarea id="contact-message" name="message" rows="4" placeholder="Comment"></textarea>
                </div>
                
                <button type="submit" class="cta-contact-btn">{{ __('home.send_message') }}</button>
            </form>
        </div>
    </section>
</div>
