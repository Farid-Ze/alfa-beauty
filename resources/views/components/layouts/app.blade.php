<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" style="scroll-behavior: smooth;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    {{-- Primary Meta Tags --}}
    <title>{{ $title ?? 'Alfa Beauty | Professional Hair Care B2B' }}</title>
    <meta name="title" content="{{ $title ?? 'Alfa Beauty | Professional Hair Care B2B' }}">
    <meta name="description" content="{{ $metaDescription ?? 'Distributor resmi produk hair care profesional. Alfaparf Milano, Farmavita, Montibello & Salsa Cosmetic. Harga grosir untuk salon & barber.' }}">
    <meta name="keywords" content="{{ $metaKeywords ?? 'hair care b2b, salon supplies, alfaparf indonesia, farmavita, keratin treatment, pewarna rambut profesional' }}">
    <meta name="author" content="Alfa Beauty">
    <meta name="robots" content="{{ $metaRobots ?? 'index, follow' }}">
    
    {{-- Open Graph / Facebook --}}
    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $title ?? 'Alfa Beauty | Professional Hair Care B2B' }}">
    <meta property="og:description" content="{{ $metaDescription ?? 'Distributor resmi produk hair care profesional untuk salon dan barber di Indonesia.' }}">
    <meta property="og:image" content="{{ $ogImage ?? asset('images/og-default.webp') }}">
    <meta property="og:site_name" content="Alfa Beauty">
    <meta property="og:locale" content="{{ app()->getLocale() == 'id' ? 'id_ID' : 'en_US' }}">
    
    {{-- Twitter --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ url()->current() }}">
    <meta name="twitter:title" content="{{ $title ?? 'Alfa Beauty | Professional Hair Care B2B' }}">
    <meta name="twitter:description" content="{{ $metaDescription ?? 'Distributor resmi produk hair care profesional untuk salon dan barber di Indonesia.' }}">
    <meta name="twitter:image" content="{{ $ogImage ?? asset('images/og-default.webp') }}">
    
    {{-- Canonical URL --}}
    <link rel="canonical" href="{{ $canonical ?? url()->current() }}">
    
    {{-- Favicon --}}
    <link rel="icon" type="image/webp" href="{{ asset('images/logo.webp') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.webp') }}">
    
    {{-- Fonts & Styles --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/' . (app()->environment('production') ? 'main.min.css' : 'main.css')) }}?v=2.1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    
    {{-- JSON-LD Structured Data --}}
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "Organization",
        "name": "Alfa Beauty",
        "url": "{{ url('/') }}",
        "logo": "{{ asset('images/logo.webp') }}",
        "description": "Distributor resmi produk hair care profesional di Indonesia",
        "address": {
            "@@type": "PostalAddress",
            "addressCountry": "ID"
        },
        "contactPoint": {
            "@@type": "ContactPoint",
            "telephone": "+62-812-3456-7890",
            "contactType": "sales"
        }
    }
    </script>
    @stack('structured-data')
    
    <style>
        /* Fix horizontal scroll - prevent testimonials track overflow */
        html, body {
            overflow-x: hidden;
        }
        
        .testimonials-clean-container {
            overflow-x: hidden !important;
        }
        
        /* Fix #toast creating white space - make it properly positioned */
        #toast, .toast {
            position: fixed !important;
            height: auto !important;
            min-height: 0 !important;
        }
        
        /* Footer bottom text colors */
        .footer-bottom-left span,
        .footer-bottom-right a {
            color: rgba(255,255,255,0.5) !important;
            font-size: 0.8125rem;
        }
        
        .footer-bottom-right a:hover {
            color: rgba(255,255,255,0.8) !important;
        }
    </style>
</head>
<body>
    {{-- Toast Notification System --}}
    <x-toast-notifications />

    @php
        $isHomepage = request()->routeIs('home');
    @endphp

    <!-- Top Bar -->
    <div class="top-bar" id="top-bar">
        <div class="top-bar-left">
            <span>{{ __('nav.promo_banner') }}</span>
        </div>
        <div class="top-bar-right">
            <!-- Language Switcher -->
            <a href="{{ route('lang.switch', 'id') }}" class="{{ app()->getLocale() == 'id' ? 'active' : '' }}">ID</a>
            <span class="top-bar-divider">|</span>
            <a href="{{ route('lang.switch', 'en') }}" class="{{ app()->getLocale() == 'en' ? 'active' : '' }}">EN</a>
            <span class="top-bar-divider">|</span>
            @auth
                <span>{{ Auth::user()->name }}</span>
                <span class="top-bar-divider">|</span>
                <a href="{{ route('orders') }}">{{ __('nav.my_orders') }}</a>
                <span class="top-bar-divider">|</span>
                <a href="{{ route('logout') }}">{{ __('nav.logout') }}</a>
            @else
                <a href="{{ route('login') }}">{{ __('nav.login') }}</a>
                <span class="top-bar-divider">|</span>
                <a href="{{ route('register') }}">{{ __('nav.register') }}</a>
            @endauth
        </div>
    </div>

    <!-- Main Navigation -->
    <nav class="nav" id="nav" x-data="{ mobileOpen: false }">
        <a href="/" class="nav-logo">Alfa Beauty</a>
        <div class="nav-menu">
            <a href="{{ route('products.index') }}" class="nav-link">{{ __('nav.products') }}</a>
            <a href="{{ route('home') }}#brands" class="nav-link">{{ __('nav.brands') }}</a>
            <a href="{{ route('home') }}#about" class="nav-link">{{ __('nav.about') }}</a>
        </div>
        <div class="nav-actions">
            @auth
                <div class="loyalty-badge">
                    <span>{{ number_format(Auth::user()->points) }} pts</span>
                    <span class="loyalty-divider">•</span>
                    <span class="{{ (Auth::user()->loyaltyTier?->name ?? 'Silver') === 'Gold' ? 'tier-gold' : 'tier-silver' }}">
                        {{ Auth::user()->loyaltyTier?->name ?? 'Silver' }}
                    </span>
                </div>
            @endauth
            <button @click="$dispatch('toggle-cart')" class="nav-cart">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 22C9.55228 22 10 21.5523 10 21C10 20.4477 9.55228 20 9 20C8.44772 20 8 20.4477 8 21C8 21.5523 8.44772 22 9 22Z"/>
                    <path d="M20 22C20.5523 22 21 21.5523 21 21C21 20.4477 20.5523 20 20 20C19.4477 20 19 20.4477 19 21C19 21.5523 19.4477 22 20 22Z"/>
                    <path d="M1 1H5L7.68 14.39C7.77144 14.8504 8.02191 15.264 8.38755 15.5583C8.75318 15.8526 9.2107 16.009 9.68 16H19.4C19.8693 16.009 20.3268 15.8526 20.6925 15.5583C21.0581 15.264 21.3086 14.8504 21.4 14.39L23 6H6"/>
                </svg>
                <livewire:cart-counter />
            </button>
            
            <!-- Hamburger Button (Mobile Only) -->
            <button class="nav-hamburger" :class="{ 'active': mobileOpen }" @click="mobileOpen = !mobileOpen" aria-label="Toggle menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
        
        <!-- Mobile Overlay -->
        <div class="mobile-overlay" :class="{ 'active': mobileOpen }" @click="mobileOpen = false"></div>
        
        <!-- Mobile Drawer -->
        <div class="mobile-drawer" :class="{ 'open': mobileOpen }">
            <div class="mobile-drawer-header">
                <span class="mobile-drawer-logo">Alfa Beauty</span>
                <button class="mobile-drawer-close" @click="mobileOpen = false" aria-label="Close menu">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="mobile-drawer-nav">
                <a href="{{ route('products.index') }}" @click="mobileOpen = false">{{ __('nav.products') }}</a>
                <a href="{{ route('home') }}#brands" @click="mobileOpen = false">{{ __('nav.brands') }}</a>
                <a href="{{ route('home') }}#about" @click="mobileOpen = false">{{ __('nav.about') }}</a>
            </div>
            <div class="mobile-drawer-auth">
                @auth
                    <a href="{{ route('orders') }}">{{ __('nav.my_orders') }}</a>
                    <a href="{{ route('logout') }}">{{ __('nav.logout') }}</a>
                @else
                    <a href="{{ route('login') }}">{{ __('nav.login') }}</a>
                    <a href="{{ route('register') }}">{{ __('nav.register') }}</a>
                @endauth
            </div>
        </div>
    </nav>

    {{ $slot }}

    <!-- Back to Top Button -->
    <button id="back-to-top" class="back-to-top" aria-label="Back to top">
        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
        </svg>
    </button>

    <!-- Footer - Multi-column Layout (same bg as CTA) -->
    <footer class="footer-mooncup">
        <div class="footer-main-grid">
            <!-- Column 1: Subscribe -->
            <div class="footer-col footer-col-subscribe">
                <h4>{{ __('nav.subscribe') }}</h4>
                <p>{{ __('nav.subscribe_desc') }}</p>
                <form class="footer-subscribe-form" action="#" method="POST">
                    @csrf
                    <input type="email" placeholder="Email" required>
                    <button type="submit">→</button>
                </form>
                <p class="footer-legal-text">{{ __('nav.subscribe_terms') }}</p>
                <div class="footer-social">
                    <a href="https://facebook.com/alfabeauty" target="_blank" rel="noopener" aria-label="Facebook">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
                        </svg>
                    </a>
                    <a href="https://instagram.com/alfabeauty" target="_blank" rel="noopener" aria-label="Instagram">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
                            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/>
                        </svg>
                    </a>
                    <a href="https://tiktok.com/@alfabeauty" target="_blank" rel="noopener" aria-label="TikTok">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/>
                        </svg>
                    </a>
                    <a href="https://wa.me/{{ config('services.whatsapp.business_number') }}" target="_blank" rel="noopener" aria-label="WhatsApp">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Column 2: Brand -->
            <div class="footer-col footer-col-brand">
                <div class="footer-brand-logo">
                    <span class="footer-brand-name">Alfa Beauty</span>
                </div>
                <p class="footer-brand-desc">{{ __('nav.footer_desc') }}</p>
            </div>

            <!-- Column 3: About Links -->
            <div class="footer-col footer-col-links">
                <h4>{{ __('nav.about') }}</h4>
                <ul>
                    <li><a href="{{ route('home') }}#about">{{ __('nav.our_story') }}</a></li>
                    <li><a href="/blog">{{ __('nav.blog') }}</a></li>
                    <li><a href="{{ route('home') }}#about">{{ __('nav.our_impact') }}</a></li>
                </ul>
            </div>

            <!-- Column 4: Contact Links -->
            <div class="footer-col footer-col-links">
                <h4>{{ __('nav.contact') }}</h4>
                <ul>
                    <li><a href="{{ route('register') }}">{{ __('nav.become_partner') }}</a></li>
                    <li><a href="/careers">{{ __('nav.careers') }}</a></li>
                    <li><a href="/contact">{{ __('nav.get_in_touch') }}</a></li>
                </ul>
            </div>

            <!-- Column 5: Help Links -->
            <div class="footer-col footer-col-links">
                <h4>{{ __('nav.help') }}</h4>
                <ul>
                    <li><a href="/faq">{{ __('nav.faq') }}</a></li>
                    <li><a href="/shipping">{{ __('nav.shipping') }}</a></li>
                    <li><a href="/returns">{{ __('nav.returns') }}</a></li>
                    <li><a href="{{ route('login') }}">{{ __('nav.business_login') }}</a></li>
                </ul>
            </div>
        </div>

        <!-- Bottom Bar -->
        <div class="footer-bottom">
            <div class="footer-bottom-left">
                <span>© {{ date('Y') }} Alfa Beauty</span>
            </div>
            <div class="footer-bottom-right">
                <a href="/refund">{{ __('nav.refund_policy') }}</a>
                <a href="/privacy">{{ __('nav.privacy') }}</a>
                <a href="/terms">{{ __('nav.terms') }}</a>
            </div>
        </div>
    </footer>

    <!-- Cart Drawer -->
    <livewire:cart-drawer />

    <!-- Toast Notification -->
    <div id="toast" class="toast"></div>

    <script>
        // Nav scroll behavior - applies to all pages
        const nav = document.getElementById('nav');
        const topBar = document.getElementById('top-bar');
        const backToTop = document.getElementById('back-to-top');
        
        window.addEventListener('scroll', () => {
            const scrolled = window.scrollY > 50;
            nav.classList.toggle('scrolled', scrolled);
            topBar.classList.toggle('scrolled', scrolled);
            
            // Show/hide back to top button
            if (window.scrollY > 500) {
                backToTop.classList.add('visible');
            } else {
                backToTop.classList.remove('visible');
            }
        });
        
        // Back to top click handler
        backToTop.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // Basic toast function
        window.showToast = function(message) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }

        // Listen for Livewire events
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('product-added-to-cart', (data) => {
                window.showToast(data.name + ' ditambahkan ke keranjang');
            });
        });
    </script>
    @livewireScripts
</body>
</html>
