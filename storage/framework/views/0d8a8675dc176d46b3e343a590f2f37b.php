<!DOCTYPE html>
<html lang="<?php echo e(app()->getLocale()); ?>" style="scroll-behavior: smooth;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    
    
    <title><?php echo e($title ?? 'Alfa Beauty | Professional Hair Care B2B'); ?></title>
    <meta name="title" content="<?php echo e($title ?? 'Alfa Beauty | Professional Hair Care B2B'); ?>">
    <meta name="description" content="<?php echo e($metaDescription ?? 'Distributor resmi produk hair care profesional. Alfaparf Milano, Farmavita, Montibello & Salsa Cosmetic. Harga grosir untuk salon & barber.'); ?>">
    <meta name="keywords" content="<?php echo e($metaKeywords ?? 'hair care b2b, salon supplies, alfaparf indonesia, farmavita, keratin treatment, pewarna rambut profesional'); ?>">
    <meta name="author" content="Alfa Beauty">
    <meta name="robots" content="<?php echo e($metaRobots ?? 'index, follow'); ?>">
    
    
    <meta property="og:type" content="<?php echo e($ogType ?? 'website'); ?>">
    <meta property="og:url" content="<?php echo e(url()->current()); ?>">
    <meta property="og:title" content="<?php echo e($title ?? 'Alfa Beauty | Professional Hair Care B2B'); ?>">
    <meta property="og:description" content="<?php echo e($metaDescription ?? 'Distributor resmi produk hair care profesional untuk salon dan barber di Indonesia.'); ?>">
    <meta property="og:image" content="<?php echo e($ogImage ?? asset('images/og-default.webp')); ?>">
    <meta property="og:site_name" content="Alfa Beauty">
    <meta property="og:locale" content="<?php echo e(app()->getLocale() == 'id' ? 'id_ID' : 'en_US'); ?>">
    
    
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?php echo e(url()->current()); ?>">
    <meta name="twitter:title" content="<?php echo e($title ?? 'Alfa Beauty | Professional Hair Care B2B'); ?>">
    <meta name="twitter:description" content="<?php echo e($metaDescription ?? 'Distributor resmi produk hair care profesional untuk salon dan barber di Indonesia.'); ?>">
    <meta name="twitter:image" content="<?php echo e($ogImage ?? asset('images/og-default.webp')); ?>">
    
    
    <link rel="canonical" href="<?php echo e($canonical ?? url()->current()); ?>">
    
    
    <link rel="icon" type="image/webp" href="<?php echo e(asset('images/logo.webp')); ?>">
    <link rel="apple-touch-icon" href="<?php echo e(asset('images/apple-touch-icon.webp')); ?>">
    
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(asset('css/' . (app()->environment('production') ? 'main.min.css' : 'main.css'))); ?>?v=2.1">
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>

    
    
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "Alfa Beauty",
        "url": "<?php echo e(url('/')); ?>",
        "logo": "<?php echo e(asset('images/logo.webp')); ?>",
        "description": "Distributor resmi produk hair care profesional di Indonesia",
        "address": {
            "@type": "PostalAddress",
            "addressCountry": "ID"
        },
        "contactPoint": {
            "@type": "ContactPoint",
            "telephone": "+62-812-3456-7890",
            "contactType": "sales"
        }
    }
    </script>
    <?php echo $__env->yieldPushContent('structured-data'); ?>
</head>
<body>
    
    <?php if (isset($component)) { $__componentOriginal704196272d5e2debce23ffdbf1a3fb23 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal704196272d5e2debce23ffdbf1a3fb23 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.toast-notifications','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('toast-notifications'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal704196272d5e2debce23ffdbf1a3fb23)): ?>
<?php $attributes = $__attributesOriginal704196272d5e2debce23ffdbf1a3fb23; ?>
<?php unset($__attributesOriginal704196272d5e2debce23ffdbf1a3fb23); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal704196272d5e2debce23ffdbf1a3fb23)): ?>
<?php $component = $__componentOriginal704196272d5e2debce23ffdbf1a3fb23; ?>
<?php unset($__componentOriginal704196272d5e2debce23ffdbf1a3fb23); ?>
<?php endif; ?>

    <?php
        $isHomepage = request()->routeIs('home');
    ?>

    <!-- Top Bar -->
    <div class="top-bar" id="top-bar">
        <div class="top-bar-left">
            <span><?php echo e(__('nav.promo_banner')); ?></span>
        </div>
        <div class="top-bar-right">
            <!-- Language Switcher -->
            <a href="<?php echo e(route('lang.switch', 'id')); ?>" class="<?php echo e(app()->getLocale() == 'id' ? 'active' : ''); ?>">ID</a>
            <span class="top-bar-divider">|</span>
            <a href="<?php echo e(route('lang.switch', 'en')); ?>" class="<?php echo e(app()->getLocale() == 'en' ? 'active' : ''); ?>">EN</a>
            <span class="top-bar-divider">|</span>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->check()): ?>
                <span><?php echo e(Auth::user()->name); ?></span>
                <span class="top-bar-divider">|</span>
                <a href="<?php echo e(route('orders')); ?>"><?php echo e(__('nav.my_orders')); ?></a>
                <span class="top-bar-divider">|</span>
                <a href="<?php echo e(route('logout')); ?>"><?php echo e(__('nav.logout')); ?></a>
            <?php else: ?>
                <a href="<?php echo e(route('login')); ?>"><?php echo e(__('nav.login')); ?></a>
                <span class="top-bar-divider">|</span>
                <a href="<?php echo e(route('register')); ?>"><?php echo e(__('nav.register')); ?></a>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>

    <!-- Main Navigation -->
    <nav class="nav" id="nav" x-data="{ mobileOpen: false }">
        <a href="/" class="nav-logo">Alfa Beauty</a>
        <div class="nav-menu">
            <a href="<?php echo e(route('products.index')); ?>" class="nav-link"><?php echo e(__('nav.products')); ?></a>
            <a href="<?php echo e(route('home')); ?>#brands" class="nav-link"><?php echo e(__('nav.brands')); ?></a>
            <a href="<?php echo e(route('home')); ?>#about" class="nav-link"><?php echo e(__('nav.about')); ?></a>
        </div>
        <div class="nav-actions">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->check()): ?>
                <div class="loyalty-badge">
                    <span><?php echo e(number_format(Auth::user()->points)); ?> pts</span>
                    <span class="loyalty-divider">•</span>
                    <span class="<?php echo e((Auth::user()->loyaltyTier?->name ?? 'Silver') === 'Gold' ? 'tier-gold' : 'tier-silver'); ?>">
                        <?php echo e(Auth::user()->loyaltyTier?->name ?? 'Silver'); ?>

                    </span>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <button @click="$dispatch('toggle-cart')" class="nav-cart">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 22C9.55228 22 10 21.5523 10 21C10 20.4477 9.55228 20 9 20C8.44772 20 8 20.4477 8 21C8 21.5523 8.44772 22 9 22Z"/>
                    <path d="M20 22C20.5523 22 21 21.5523 21 21C21 20.4477 20.5523 20 20 20C19.4477 20 19 20.4477 19 21C19 21.5523 19.4477 22 20 22Z"/>
                    <path d="M1 1H5L7.68 14.39C7.77144 14.8504 8.02191 15.264 8.38755 15.5583C8.75318 15.8526 9.2107 16.009 9.68 16H19.4C19.8693 16.009 20.3268 15.8526 20.6925 15.5583C21.0581 15.264 21.3086 14.8504 21.4 14.39L23 6H6"/>
                </svg>
                <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('cart-counter', []);

$key = null;

$key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-3810039900-0', null);

$__html = app('livewire')->mount($__name, $__params, $key);

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
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
                <a href="<?php echo e(route('products.index')); ?>" @click="mobileOpen = false"><?php echo e(__('nav.products')); ?></a>
                <a href="<?php echo e(route('home')); ?>#brands" @click="mobileOpen = false"><?php echo e(__('nav.brands')); ?></a>
                <a href="<?php echo e(route('home')); ?>#about" @click="mobileOpen = false"><?php echo e(__('nav.about')); ?></a>
            </div>
            <div class="mobile-drawer-auth">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->check()): ?>
                    <a href="<?php echo e(route('orders')); ?>"><?php echo e(__('nav.my_orders')); ?></a>
                    <a href="<?php echo e(route('logout')); ?>"><?php echo e(__('nav.logout')); ?></a>
                <?php else: ?>
                    <a href="<?php echo e(route('login')); ?>"><?php echo e(__('nav.login')); ?></a>
                    <a href="<?php echo e(route('register')); ?>"><?php echo e(__('nav.register')); ?></a>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
    </nav>

    <?php echo e($slot); ?>


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
                <h4><?php echo e(__('nav.subscribe')); ?></h4>
                <p><?php echo e(__('nav.subscribe_desc')); ?></p>
                <form class="footer-subscribe-form" action="#" method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="email" placeholder="Email" required>
                    <button type="submit">→</button>
                </form>
                <p class="footer-legal-text"><?php echo e(__('nav.subscribe_terms')); ?></p>
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
                    <a href="https://wa.me/<?php echo e(config('services.whatsapp.business_number')); ?>" target="_blank" rel="noopener" aria-label="WhatsApp">
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
                <p class="footer-brand-desc"><?php echo e(__('nav.footer_desc')); ?></p>
            </div>

            <!-- Column 3: About Links -->
            <div class="footer-col footer-col-links">
                <h4><?php echo e(__('nav.about')); ?></h4>
                <ul>
                    <li><a href="<?php echo e(route('home')); ?>#about"><?php echo e(__('nav.our_story')); ?></a></li>
                    <li><a href="/blog"><?php echo e(__('nav.blog')); ?></a></li>
                    <li><a href="<?php echo e(route('home')); ?>#about"><?php echo e(__('nav.our_impact')); ?></a></li>
                </ul>
            </div>

            <!-- Column 4: Contact Links -->
            <div class="footer-col footer-col-links">
                <h4><?php echo e(__('nav.contact')); ?></h4>
                <ul>
                    <li><a href="<?php echo e(route('register')); ?>"><?php echo e(__('nav.become_partner')); ?></a></li>
                    <li><a href="/careers"><?php echo e(__('nav.careers')); ?></a></li>
                    <li><a href="/contact"><?php echo e(__('nav.get_in_touch')); ?></a></li>
                </ul>
            </div>

            <!-- Column 5: Help Links -->
            <div class="footer-col footer-col-links">
                <h4><?php echo e(__('nav.help')); ?></h4>
                <ul>
                    <li><a href="/faq"><?php echo e(__('nav.faq')); ?></a></li>
                    <li><a href="/shipping"><?php echo e(__('nav.shipping')); ?></a></li>
                    <li><a href="/returns"><?php echo e(__('nav.returns')); ?></a></li>
                    <li><a href="<?php echo e(route('login')); ?>"><?php echo e(__('nav.business_login')); ?></a></li>
                </ul>
            </div>
        </div>

        <!-- Bottom Bar -->
        <div class="footer-bottom" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; padding: 1.5rem 2rem; border-top: 1px solid rgba(255,255,255,0.1);">
            <div class="footer-bottom-left">
                <span style="color: rgba(255,255,255,0.6); font-size: 0.8125rem;">© <?php echo e(date('Y')); ?> Alfa Beauty</span>
            </div>
            <div class="footer-bottom-right" style="display: flex; gap: 1.5rem;">
                <a href="/refund" style="color: rgba(255,255,255,0.6); font-size: 0.8125rem; text-decoration: none; transition: color 0.2s;"><?php echo e(__('nav.refund_policy')); ?></a>
                <a href="/privacy" style="color: rgba(255,255,255,0.6); font-size: 0.8125rem; text-decoration: none; transition: color 0.2s;"><?php echo e(__('nav.privacy')); ?></a>
                <a href="/terms" style="color: rgba(255,255,255,0.6); font-size: 0.8125rem; text-decoration: none; transition: color 0.2s;"><?php echo e(__('nav.terms')); ?></a>
            </div>
        </div>
    </footer>

    <!-- Cart Drawer -->
    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('cart-drawer', []);

$key = null;

$key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-3810039900-1', null);

$__html = app('livewire')->mount($__name, $__params, $key);

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>

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
    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts(); ?>

</body>
</html>
<?php /**PATH C:\Users\VCTUS\Documents\rid\27\app\resources\views/components/layouts/app.blade.php ENDPATH**/ ?>