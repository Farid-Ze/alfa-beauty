<div>
    <!-- Bold Editorial Hero -->
    <section class="brand-editorial-hero">
    <!-- Top Bar: Breadcrumb Only -->
        <div class="editorial-top">
            <nav class="breadcrumb">
                <a href="<?php echo e(route('home')); ?>"><?php echo e(__('nav.home')); ?></a>
                <span>›</span>
                <a href="<?php echo e(route('products.index')); ?>"><?php echo e(__('nav.products')); ?></a>
                <span>›</span>
                <span class="breadcrumb-current"><?php echo e($brand->name); ?></span>
            </nav>
        </div>

        <!-- Main Hero Content -->
        <div class="editorial-hero-main">
            <!-- Left: Brand Name + Tagline + CTA -->
            <div class="editorial-left-panel">
                <div class="editorial-brand-name">
                    <h1 class="brand-name-primary"><?php echo e(strtoupper(explode(' ', $brand->name)[0])); ?></h1>
                    <span class="brand-name-secondary"><?php echo e(implode(' ', array_slice(explode(' ', $brand->name), 1)) ?: 'Professional'); ?></span>
                </div>
                <p class="editorial-tagline">
                    <?php echo e(__('brand.professional_hair_care')); ?><br>
                    <?php echo e(__('brand.from')); ?> <em><?php echo e($brand->origin_country); ?></em>
                </p>
                <a href="<?php echo e(route('products.index', ['selectedBrands' => [$brand->id]])); ?>" class="editorial-explore-btn">
                    <?php echo e(__('brand.explore_products')); ?> →
                </a>
            </div>

            <!-- Center: Featured Product (Full Height) -->
            <div class="editorial-product-center">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($featuredProduct): ?>
                <a href="<?php echo e(route('products.show', $featuredProduct->slug)); ?>" class="editorial-product-wrapper">
                    <div class="editorial-product-image">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($featuredProduct->images && count($featuredProduct->images) > 0): ?>
                            <img src="<?php echo e(url($featuredProduct->images[0])); ?>" alt="<?php echo e($featuredProduct->name); ?>" loading="lazy">
                        <?php else: ?>
                            <div class="editorial-product-placeholder">
                                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="0.5">
                                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                                    <circle cx="8.5" cy="8.5" r="1.5"/>
                                    <path d="M21 15l-5-5L5 21"/>
                                </svg>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </a>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            <!-- Right: Metrics + CTA -->
            <div class="editorial-right-panel">
                <div class="editorial-metrics">
                    <div class="metric-block">
                        <span class="metric-index">01</span>
                        <span class="metric-label"><?php echo e(__('brand.products')); ?></span>
                        <span class="metric-value"><?php echo e($productCount); ?></span>
                    </div>
                    <div class="metric-block">
                        <span class="metric-index">02</span>
                        <span class="metric-label"><?php echo e(__('brand.available')); ?></span>
                        <span class="metric-value"><?php echo e(number_format($totalStock)); ?></span>
                    </div>
                    <div class="metric-block">
                        <span class="metric-index">03</span>
                        <span class="metric-label"><?php echo e(__('brand.origin')); ?></span>
                        <span class="metric-value"><?php echo e($brand->origin_country); ?></span>
                    </div>
                </div>

                <div class="editorial-cta-stack">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($featuredProduct): ?>
                    <a href="<?php echo e(route('products.show', $featuredProduct->slug)); ?>" class="editorial-btn-primary">
                        <?php echo e(__('brand.view_this_product')); ?>

                    </a>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <a href="<?php echo e(route('register')); ?>" class="editorial-btn-outline">
                        <?php echo e(__('brand.become_partner')); ?>

                    </a>
                </div>
            </div>
        </div>
    </section>


    <!-- Brand Navigator -->
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($otherBrands->count() > 0): ?>
    <section class="brand-navigator">
        <span class="nav-label"><?php echo e(__('brand.explore_other_brands')); ?></span>
        <div class="nav-brands">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $otherBrands; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $other): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a href="<?php echo e(route('brands.show', $other->slug)); ?>" class="nav-brand-item">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($other->logo_url): ?>
                    <div class="nav-brand-logo">
                        <img src="<?php echo e(url($other->logo_url)); ?>" alt="<?php echo e($other->name); ?>" loading="lazy">
                    </div>
                <?php else: ?>
                    <span class="nav-brand-name"><?php echo e($other->name); ?></span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <span class="nav-brand-origin"><?php echo e($other->origin_country); ?></span>
            </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </section>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>

<?php /**PATH C:\Users\VCTUS\Documents\rid\27\app\resources\views/livewire/brand-detail.blade.php ENDPATH**/ ?>