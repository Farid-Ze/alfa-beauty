<div>
    <!-- Page Hero with Breadcrumb -->
    <section class="page-hero" style="text-align: left;">
        <nav class="breadcrumb">
            <a href="<?php echo e(route('home')); ?>"><?php echo e(__('nav.home')); ?></a>
            <span>›</span>
            <a href="<?php echo e(route('products.index')); ?>"><?php echo e(__('nav.products')); ?></a>
            <span>›</span>
            <span style="color: var(--white);"><?php echo e($product->name); ?></span>
        </nav>
    </section>

    <!-- Product Detail Section -->
    <section class="product-detail-grid">
        
        <!-- Image Column (55%) -->
        <div class="product-gallery">
            <div class="product-main-image">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($product->images && count($product->images) > 0): ?>
                    <img src="<?php echo e(url($product->images[0])); ?>" alt="<?php echo e($product->name); ?>">
                <?php else: ?>
                    <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: var(--gray-400);">
                        <svg width="80" height="80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                
                <!-- Badges -->
                <div class="pdp-special-badge">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($product->is_featured): ?>
                        <span class="pdp-badge pdp-badge-featured"><?php echo e(__('products.best_seller')); ?></span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasCustomerPricing): ?>
                        <span class="pdp-badge pdp-badge-b2b"><?php echo e(__('products.special_price')); ?></span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>

            <!-- Thumbnails -->
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(is_array($product->images) && count($product->images) > 1): ?>
                <div class="product-thumbnails">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $product->images; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $image): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="product-thumbnail <?php echo e($index === 0 ? 'active' : ''); ?>">
                            <img src="<?php echo e(url($image)); ?>" alt="<?php echo e($product->name); ?>">
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        <!-- Details Column (45%) -->
        <div class="product-info-column">
            
            <!-- Brand & Title -->
            <div class="product-header">
                <span class="product-brand-name">
                    <?php echo e($product->brand->name ?? 'Brand'); ?>

                </span>
                <h1 class="product-title">
                    <?php echo e($product->name); ?>

                </h1>
                <!-- Product Meta (SKU + BPOM + Distributor) -->
                <p class="product-meta-line">
                    <span>SKU: <?php echo e($product->sku); ?></span>
                    <span class="meta-divider">•</span>
                    <span>BPOM: <?php echo e($product->bpom_number ?? 'NA18201200123'); ?></span>
                    <span class="meta-divider">•</span>
                    <span><?php echo e(__('nav.official_distributor')); ?></span>
                </p>
            </div>

            <!-- Pricing & Stock -->
            <div class="pricing-row">
                <div class="pricing-group b2b-price-wrapper">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasDiscount): ?>
                        <span class="price-original-strikethrough">
                            Rp <?php echo e(number_format($originalPrice, 0, ',', '.')); ?>

                        </span>
                        <span class="price-main price-current-discounted">Rp <?php echo e(number_format($currentPrice, 0, ',', '.')); ?></span>
                        <span class="price-discount-badge">
                            -<?php echo e(number_format($discountPercent, 0)); ?>%
                        </span>
                    <?php else: ?>
                        <span class="price-main">Rp <?php echo e(number_format($currentPrice, 0, ',', '.')); ?></span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <span class="price-unit">/unit</span>
                </div>
                <div class="stock-indicator">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($product->in_stock): ?>
                        <span class="stock-dot in-stock"></span>
                        <span class="stock-text in-stock"><?php echo e(__('general.in_stock')); ?> (<?php echo e($product->stock); ?>)</span>
                    <?php else: ?>
                        <span class="stock-dot out-of-stock"></span>
                        <span class="stock-text out-of-stock"><?php echo e(__('general.out_of_stock')); ?></span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>

            <!-- Volume Pricing Tiers (B2B) -->
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($priceTiers) > 0): ?>
                <div class="volume-pricing-section">
                    <h4 class="volume-pricing-title">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        <?php echo e(__('products.volume_discounts')); ?>

                    </h4>
                    <div class="volume-tiers">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $priceTiers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tier): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $isActiveTier = $hasVolumePricing && $quantity >= $tier['min_qty'] && ($tier['max_qty'] === null || $quantity <= $tier['max_qty']);
                            ?>
                            <div class="volume-tier <?php echo e($isActiveTier ? 'active' : ''); ?>">
                                <div class="volume-tier-qty"><?php echo e($tier['label']); ?> <?php echo e(__('products.unit')); ?></div>
                                <div class="volume-tier-price">Rp <?php echo e(number_format($tier['unit_price'], 0, ',', '.')); ?></div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($tier['discount_percent']): ?>
                                    <div class="volume-tier-discount"><?php echo e(__('products.save_percent', ['percent' => number_format($tier['discount_percent'], 0)])); ?></div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <!-- Customer-specific pricing banner -->
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasCustomerPricing): ?>
                <div class="customer-price-banner">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <div>
                        <div class="customer-price-banner-title"><?php echo e(__('products.special_price_title')); ?></div>
                        <div class="customer-price-banner-subtitle"><?php echo e(__('products.special_price_desc')); ?></div>
                    </div>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <!-- Quantity & Subtotal Section (Livewire Reactive) -->
            <div class="quantity-section">
                <!-- Quantity Control -->
                <div class="quantity-row">
                    <label class="quantity-label"><?php echo e(__('products.quantity')); ?></label>
                    <div class="quantity-controls">
                        <button wire:click="decrementQuantity" class="quantity-btn" <?php echo e($quantity <= 1 ? 'disabled' : ''); ?>>−</button>
                        <input 
                            type="number" 
                            wire:model.live.debounce.300ms="quantity" 
                            class="quantity-input" 
                            min="1" 
                            max="<?php echo e($product->stock); ?>"
                        >
                        <button wire:click="incrementQuantity" class="quantity-btn" <?php echo e($quantity >= $product->stock ? 'disabled' : ''); ?>>+</button>
                    </div>
                </div>

                <!-- Dynamic Subtotal -->
                <div class="subtotal-row">
                    <span class="subtotal-label"><?php echo e(__('cart.subtotal')); ?></span>
                    <div class="subtotal-value">
                        <span class="subtotal-price">Rp <?php echo e(number_format($lineTotal, 0, ',', '.')); ?></span>
                        <span class="subtotal-points">+<?php echo e($product->points * $quantity); ?> <?php echo e(__('general.pts')); ?></span>
                    </div>
                </div>

                <!-- Full Width Add to Cart Button -->
                <button 
                    wire:click="addToCart"
                    wire:loading.attr="disabled"
                    class="btn add-to-cart-btn"
                    <?php echo e(!$product->in_stock ? 'disabled' : ''); ?>

                >
                    <span wire:loading.remove wire:target="addToCart">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($product->in_stock): ?>
                            <?php echo e(__('products.add_to_cart')); ?> (<?php echo e($quantity); ?> unit)
                        <?php else: ?>
                            <?php echo e(__('general.out_of_stock')); ?>

                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </span>
                    <span wire:loading wire:target="addToCart"><?php echo e(__('general.loading')); ?></span>
                </button>
            </div>

            <!-- Product Specs Link (B2B Style) -->
            <div class="product-specs-link">
                <a href="#" class="specs-download-btn">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span><?php echo e(__('products.download_specs')); ?></span>
                    <span class="specs-format">PDF</span>
                </a>
                <p class="specs-note"><?php echo e(__('products.specs_note')); ?></p>
            </div>

        </div> <!-- End Info Column -->
    </section> <!-- End Grid -->

    <!-- Mobile Sticky Add-to-Cart Bar -->
    <div class="pdp-sticky-cta">
        <div class="sticky-cta-inner">
            <div class="sticky-cta-price">
                <span class="sticky-price">Rp <?php echo e(number_format($lineTotal, 0, ',', '.')); ?></span>
                <span class="sticky-points"><?php echo e($quantity > 1 ? $quantity . ' unit' : ''); ?></span>
            </div>
            <button 
                wire:click="addToCart"
                wire:loading.attr="disabled"
                class="btn sticky-cta-btn"
                <?php echo e(!$product->in_stock ? 'disabled' : ''); ?>

            >
                <span wire:loading.remove wire:target="addToCart"><?php echo e(__('products.add_to_cart')); ?></span>
                <span wire:loading wire:target="addToCart"><?php echo e(__('general.loading')); ?></span>
            </button>
        </div>
    </div>
</div>
<?php /**PATH C:\Users\VCTUS\Documents\rid\27\app\resources\views/livewire/product-detail-page.blade.php ENDPATH**/ ?>