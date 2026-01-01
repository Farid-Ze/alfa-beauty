<div>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($products->isNotEmpty()): ?>
        <section class="buy-it-again-section">
            <div class="section-header">
                <h2 class="section-title"><?php echo e(__('orders.buy_again')); ?></h2>
                <p class="section-subtitle"><?php echo e(__('orders.buy_again_desc')); ?></p>
            </div>
            
            <div class="buy-again-grid">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $productImages = is_array($product->images) ? $product->images : [];
                        $priceInfo = $prices[$product->id] ?? null;
                        $displayPrice = $priceInfo['price'] ?? $product->base_price;
                        $originalPrice = $priceInfo['original_price'] ?? $product->base_price;
                        $hasDiscount = $priceInfo && $priceInfo['price'] < $originalPrice;
                        $priceSource = $priceInfo['source'] ?? 'base_price';
                        $discountPercent = $hasDiscount ? round((1 - $displayPrice / $originalPrice) * 100) : 0;
                    ?>
                    <div class="buy-again-card">
                        <a href="<?php echo e(route('products.show', $product->slug)); ?>" class="buy-again-image">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($productImages) > 0): ?>
                                <img src="<?php echo e(url($productImages[0])); ?>" alt="<?php echo e($product->name); ?>">
                            <?php else: ?>
                                <div class="placeholder-image">
                                    <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            
                            <!-- B2B Discount Badge -->
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasDiscount): ?>
                                <span class="cart-item-badge">-<?php echo e($discountPercent); ?>%</span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </a>
                        
                        <div class="buy-again-info">
                            <span class="buy-again-brand"><?php echo e($product->brand->name ?? 'Brand'); ?></span>
                            <h3 class="buy-again-name">
                                <a href="<?php echo e(route('products.show', $product->slug)); ?>"><?php echo e($product->name); ?></a>
                            </h3>
                            <div class="buy-again-price">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasDiscount): ?>
                                    <span class="price-original">Rp <?php echo e(number_format($originalPrice, 0, ',', '.')); ?></span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <span class="price-current <?php echo e($hasDiscount ? 'price-discounted' : ''); ?>">
                                    Rp <?php echo e(number_format($displayPrice, 0, ',', '.')); ?>

                                </span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($priceSource === 'customer_price_list'): ?>
                                    <span class="price-source-tag"><?php echo e(__('products.special_price')); ?></span>
                                <?php elseif($priceSource === 'volume_tier'): ?>
                                    <span class="price-source-tag"><?php echo e(__('products.volume_discounts')); ?></span>
                                <?php elseif($priceSource === 'loyalty_tier'): ?>
                                    <span class="price-source-tag"><?php echo e(__('products.loyalty_discount')); ?></span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>
                        
                        <button 
                            wire:click="addToCart(<?php echo e($product->id); ?>)"
                            wire:loading.attr="disabled"
                            class="buy-again-btn"
                        >
                            <span wire:loading.remove wire:target="addToCart(<?php echo e($product->id); ?>)">+ <?php echo e(__('orders.buy_again')); ?></span>
                            <span wire:loading wire:target="addToCart(<?php echo e($product->id); ?>)"><?php echo e(__('general.loading')); ?>...</span>
                        </button>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </section>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php /**PATH C:\Users\VCTUS\Documents\rid\27\app\resources\views/livewire/buy-it-again.blade.php ENDPATH**/ ?>