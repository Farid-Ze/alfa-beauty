<div 
    class="cart-drawer-overlay"
    x-data="{ open: false }"
    x-show="open"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @toggle-cart.window="open = !open"
    @open-cart.window="open = true"
    @keydown.escape.window="open = false"
    style="display: none;"
>
    <div class="cart-drawer-backdrop" @click="open = false"></div>
    <aside class="cart-drawer" x-show="open" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">
        <div class="cart-header">
            <h2><?php echo e(__('cart.shopping_cart')); ?> <span class="cart-count">(<?php echo e($itemCount ?? count($items)); ?>)</span></h2>
            <button class="close-btn" @click="open = false" aria-label="<?php echo e(__('general.close')); ?>">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="cart-items">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $hasDiscount = isset($item['discount_percent']) && $item['discount_percent'] > 0;
                    $product = $item['product'];
                    $productImages = is_array($product->images) ? $product->images : (is_object($product->images) ? $product->images->toArray() : []);
                ?>
                <div class="cart-item" wire:key="cart-item-<?php echo e($item['id']); ?>">
                    <!-- Image -->
                    <div class="cart-item-image">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($productImages) > 0): ?>
                            <img src="<?php echo e(url($productImages[0])); ?>" alt="<?php echo e($product->name); ?>">
                        <?php else: ?>
                            <div class="cart-item-placeholder">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        
                        <!-- B2B Discount Badge -->
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasDiscount): ?>
                            <span class="cart-item-badge">
                                -<?php echo e(number_format($item['discount_percent'], 0)); ?>%
                            </span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    
                    <!-- Content -->
                    <div class="cart-item-content">
                        <h4 class="cart-item-name"><?php echo e($product->name); ?></h4>
                        
                        <!-- Price Source Badge (B2B indicator) -->
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($item['price_source']) && $item['price_source'] === 'customer_price_list'): ?>
                            <span class="price-source-tag"><?php echo e(__('products.special_price')); ?></span>
                        <?php elseif(isset($item['price_source']) && $item['price_source'] === 'volume_tier'): ?>
                            <span class="price-source-tag"><?php echo e(__('products.volume_discounts')); ?></span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        <div class="cart-item-meta">
                            <div class="cart-item-pricing">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasDiscount): ?>
                                    <span class="cart-item-price-original">
                                        Rp <?php echo e(number_format($item['original_price'] * $item['quantity'], 0, ',', '.')); ?>

                                    </span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <span class="cart-item-price <?php echo e($hasDiscount ? 'price-current-discounted' : ''); ?>">
                                    Rp <?php echo e(number_format($item['line_total'], 0, ',', '.')); ?>

                                </span>
                                <span class="cart-item-pts">+<?php echo e($product->points * $item['quantity']); ?> <?php echo e(__('general.pts')); ?></span>
                            </div>
                            <div class="cart-item-actions">
                                <?php
                                    $orderIncrement = $product->order_increment ?? 1;
                                    $minOrderQty = $product->min_order_qty ?? 1;
                                    $canDecrement = $item['quantity'] > $minOrderQty;
                                ?>
                                <div class="cart-item-qty">
                                    <button 
                                        wire:click="decrementItem(<?php echo e($item['id']); ?>)"
                                        wire:loading.attr="disabled"
                                        wire:target="decrementItem"
                                        <?php echo e(!$canDecrement ? 'disabled' : ''); ?>

                                        title="<?php echo e(!$canDecrement ? __('cart.min_qty_reached') : '-' . $orderIncrement); ?>"
                                    >−</button>
                                    <span><?php echo e($item['quantity']); ?></span>
                                    <button 
                                        wire:click="incrementItem(<?php echo e($item['id']); ?>)"
                                        wire:loading.attr="disabled"
                                        wire:target="incrementItem"
                                        title="+<?php echo e($orderIncrement); ?>"
                                    >+</button>
                                </div>
                                <button 
                                    class="cart-item-remove" 
                                    wire:click="removeItem(<?php echo e($item['id']); ?>)"
                                    wire:loading.class="opacity-50"
                                    wire:target="removeItem(<?php echo e($item['id']); ?>)"
                                ><?php echo e(__('cart.remove')); ?></button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="cart-empty">
                    <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    <p><?php echo e(__('cart.cart_empty')); ?></p>
                    <a href="<?php echo e(route('products.index')); ?>" class="btn btn-sm"><?php echo e(__('cart.view_products')); ?></a>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($items) > 0): ?>
        <?php
            $totalPoints = collect($items)->sum(fn($item) => $item['product']->points * $item['quantity']);
            $totalSavings = collect($items)->filter(fn($item) => isset($item['discount_percent']) && $item['discount_percent'] > 0)
                ->sum(fn($item) => ($item['original_price'] - $item['unit_price']) * $item['quantity']);
        ?>
        <div class="cart-footer">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($totalSavings > 0): ?>
            <div class="savings-banner">
                <span class="savings-banner-text">🎉 <?php echo e(__('checkout.you_save')); ?> </span>
                <span class="savings-banner-amount">Rp <?php echo e(number_format($totalSavings, 0, ',', '.')); ?></span>
                <span class="savings-banner-text"> <?php echo e(__('checkout.with_b2b_price')); ?></span>
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            
            <div class="cart-subtotal">
                <div class="subtotal-left">
                    <span class="subtotal-label"><?php echo e(__('cart.subtotal')); ?></span>
                </div>
                <div class="subtotal-right">
                    <span class="subtotal-amount">Rp <?php echo e(number_format($subtotal, 0, ',', '.')); ?></span>
                    <span class="subtotal-pts">+<?php echo e($totalPoints); ?> <?php echo e(__('general.pts')); ?></span>
                </div>
            </div>

            <a 
                href="/checkout" 
                class="btn btn-block checkout-btn"
                x-data="{ loading: false }"
                x-on:click="loading = true"
                :class="{ 'btn-loading': loading }"
            >
                <span x-show="!loading"><?php echo e(__('cart.checkout')); ?></span>
                <span x-show="loading" x-cloak><?php echo e(__('general.loading')); ?>...</span>
            </a>
        </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </aside>
</div>
<?php /**PATH C:\Users\VCTUS\Documents\rid\27\app\resources\views/livewire/cart-drawer.blade.php ENDPATH**/ ?>