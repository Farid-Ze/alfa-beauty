<?php
    // Use B2B price info if provided, otherwise fall back to product defaults
    $displayPrice = $priceInfo['price'] ?? $product->price;
    $originalPrice = $priceInfo['original_price'] ?? $product->base_price;
    $priceSource = $priceInfo['source'] ?? 'base_price';
    $hasDiscount = $priceInfo && isset($priceInfo['price']) && $priceInfo['price'] < $originalPrice;
    $discountPercent = $hasDiscount ? round((1 - $displayPrice / $originalPrice) * 100) : 0;
    $hasVolumePricing = $product->has_volume_pricing || ($priceSource === 'volume_tier');
?>
<article class="product-card">
    <a href="<?php echo e(route('products.show', $product->slug)); ?>" class="product-image">
        <img src="<?php echo e(isset($product->images[0]) ? url($product->images[0]) : asset('images/product-color.png')); ?>" alt="<?php echo e($product->name); ?>" loading="lazy">
        
        <!-- B2B Discount Badge -->
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasDiscount): ?>
            <span class="cart-item-badge" style="position: absolute; top: 8px; right: 8px;">-<?php echo e($discountPercent); ?>%</span>
        <?php elseif($hasVolumePricing): ?>
            <span class="cart-item-badge" style="position: absolute; top: 8px; right: 8px;">Volume</span>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </a>
    <div class="product-details">
        <span class="product-brand"><?php echo e($product->brand->name ?? __('products.brand')); ?></span>
        <h3 class="product-name">
            <a href="<?php echo e(route('products.show', $product->slug)); ?>"><?php echo e($product->name); ?></a>
        </h3>
        <div class="product-pricing">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasDiscount): ?>
                <span class="price-original" style="text-decoration: line-through; color: var(--neutral-500); font-size: 0.75rem;">
                    Rp <?php echo e(number_format($originalPrice, 0, ',', '.')); ?>

                </span>
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
            <?php elseif($hasVolumePricing): ?>
                <span class="price-source-tag"><?php echo e(__('products.view_details')); ?></span>
            <?php else: ?>
                <span class="price-points">+<?php echo e($product->points); ?> <?php echo e(__('general.pts')); ?></span>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>
    <button class="btn-quick-add" wire:click="addToCart" wire:loading.attr="disabled">
        <span wire:loading.remove wire:target="addToCart">+ <?php echo e(__('products.quick_add')); ?></span>
        <span wire:loading wire:target="addToCart"><?php echo e(__('general.loading')); ?></span>
    </button>
</article>
<?php /**PATH C:\Users\VCTUS\Documents\rid\27\app\resources\views/livewire/product-card.blade.php ENDPATH**/ ?>