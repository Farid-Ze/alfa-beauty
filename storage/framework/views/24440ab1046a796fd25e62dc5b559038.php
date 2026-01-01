<div>
    <!-- Page Hero -->
    <section class="page-hero">
        <h1><?php echo e(__('orders.my_orders')); ?></h1>
        <p class="page-hero-subtitle"><?php echo e(__('orders.my_orders_subtitle')); ?></p>
    </section>

    <div class="orders-container">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($orders->isEmpty()): ?>
            <div class="order-card empty-state">
                <p class="empty-state-text"><?php echo e(__('orders.no_orders_desc')); ?></p>
                <a href="<?php echo e(route('products.index')); ?>" class="btn"><?php echo e(__('orders.start_shopping')); ?></a>
            </div>
        <?php else: ?>
            <div class="orders-list">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <span class="order-number"><?php echo e($order->order_number); ?></span>
                                <span class="order-date"><?php echo e($order->created_at->format('d M Y, H:i')); ?></span>
                            </div>
                            <div class="order-badges">
                                <span class="order-status-badge order-status-badge--<?php echo e($order->status); ?>">
                                    <?php echo e(__('orders.' . $order->status)); ?>

                                </span>
                                
                                <?php
                                    $pointsEarned = $order->pointTransactions->where('type', 'earn')->sum('amount');
                                ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($pointsEarned > 0): ?>
                                    <span class="order-points-badge">
                                        +<?php echo e(number_format($pointsEarned)); ?> <?php echo e(__('general.pts')); ?>

                                    </span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="order-items">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $order->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="order-item">
                                    <div class="order-item-image">
                                        <img src="<?php echo e(isset($item->product->images[0]) ? url($item->product->images[0]) : asset('images/product-color.png')); ?>" alt="<?php echo e($item->product->name); ?>">
                                    </div>
                                    <div class="order-item-info">
                                        <h4 class="order-item-name"><?php echo e($item->product->name); ?></h4>
                                        <p class="order-item-meta"><?php echo e(__('orders.qty')); ?>: <?php echo e($item->quantity); ?> × Rp <?php echo e(number_format($item->unit_price, 0, ',', '.')); ?></p>
                                    </div>
                                    <div class="order-item-total">
                                        <p class="order-item-price">Rp <?php echo e(number_format($item->total_price, 0, ',', '.')); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                            <div class="order-footer">
                                <span class="order-footer-label"><?php echo e(__('checkout.total')); ?></span>
                                <span class="order-footer-amount">Rp <?php echo e(number_format($order->total_amount, 0, ',', '.')); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    <!-- Buy It Again Section -->
    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('buy-it-again');

$key = null;

$key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-5377212-0', null);

$__html = app('livewire')->mount($__name, $__params, $key);

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
</div>


<?php /**PATH C:\Users\VCTUS\Documents\rid\27\app\resources\views/livewire/my-orders.blade.php ENDPATH**/ ?>