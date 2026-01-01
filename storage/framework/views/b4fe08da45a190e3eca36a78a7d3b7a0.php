<div>
    <!-- Page Hero -->
    <section class="page-hero">
        <h1 class="heading-massive"><?php echo e(__('products.catalog')); ?></h1>
        <p><?php echo e(__('products.catalog_subtitle')); ?></p>
    </section>

    <!-- Mobile Filter Toggle -->
    <button 
        class="mobile-filter-toggle" 
        x-data
        x-on:click="document.querySelector('.sidebar').classList.toggle('active')"
    >
        ☰ <?php echo e(__('products.filter_products')); ?>

    </button>

    <!-- Content Area -->
    <section class="content-grid">
        
        <!-- Sidebar Filters -->
        <aside class="sidebar">
            
            <!-- Search -->
            <div class="sidebar-section">
                <label class="sidebar-label"><?php echo e(__('general.search')); ?></label>
                <input 
                    wire:model.live.debounce.300ms="search" 
                    type="text" 
                    placeholder="<?php echo e(__('products.search_placeholder')); ?>" 
                    class="sidebar-input"
                >
            </div>

            <!-- Categories -->
            <div class="sidebar-section">
                <label class="sidebar-label"><?php echo e(__('products.category')); ?></label>
                <div class="filter-list">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <label class="filter-item">
                            <input wire:model.live="selectedCategories" type="checkbox" value="<?php echo e($category->id); ?>">
                            <span><?php echo e($category->name); ?></span>
                        </label>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>

            <!-- Brands -->
            <div class="sidebar-section">
                <label class="sidebar-label"><?php echo e(__('products.brand')); ?></label>
                <div class="filter-list">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $brands; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $brand): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <label class="filter-item">
                            <input wire:model.live="selectedBrands" type="checkbox" value="<?php echo e($brand->id); ?>">
                            <span><?php echo e($brand->name); ?></span>
                        </label>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>

            <!-- Price Range -->
            <div class="sidebar-section">
                <label class="sidebar-label"><?php echo e(__('products.price_range')); ?></label>
                <div class="price-range-inputs">
                    <input wire:model.live.debounce.500ms="priceMin" type="number" placeholder="<?php echo e(__('products.min_price')); ?>" class="sidebar-input">
                    <span style="color: var(--gray-400);">–</span>
                    <input wire:model.live.debounce.500ms="priceMax" type="number" placeholder="<?php echo e(__('products.max_price')); ?>" class="sidebar-input">
                </div>
            </div>

            <!-- Clear Filters -->
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($selectedCategories) || !empty($selectedBrands) || $priceMin || $priceMax || $search): ?>
                <button 
                    wire:click="clearFilters" 
                    class="clear-filters-btn"
                >
                    <?php echo e(__('products.clear_filters')); ?>

                </button>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </aside>

        <!-- Products Grid -->
        <div>
            <!-- Toolbar -->
            <div class="toolbar">
                <p class="toolbar-info">
                    <?php echo e(__('products.showing_results', ['count' => $products->total()])); ?>

                </p>
                <div class="toolbar-sort">
                    <label><?php echo e(__('products.sort_by')); ?>:</label>
                    <select wire:model.live="sort">
                        <option value="latest"><?php echo e(__('products.latest')); ?></option>
                        <option value="price_asc"><?php echo e(__('products.price_low_high')); ?></option>
                        <option value="price_desc"><?php echo e(__('products.price_high_low')); ?></option>
                        <option value="name_asc"><?php echo e(__('products.name_a_z')); ?></option>
                        <option value="name_desc"><?php echo e(__('products.name_z_a')); ?></option>
                    </select>
                </div>
            </div>

            <!-- Grid -->
            <div wire:loading.class="opacity-50" class="products-grid">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('product-card', ['product' => $product,'priceInfo' => $prices[$product->id] ?? null]);

$key = $product->id;

$key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-1167967903-0', $product->id);

$__html = app('livewire')->mount($__name, $__params, $key);

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="no-products-message">
                        <p style="font-family: var(--font-display); font-size: 1.5rem; margin-bottom: var(--space-sm);"><?php echo e(__('products.no_products')); ?></p>
                        <p style="font-size: 0.875rem;"><?php echo e(__('products.try_different_filter')); ?></p>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            <!-- Pagination -->
            <div class="pagination-container">
                <?php echo e($products->links()); ?>

            </div>
        </div>
    </section>
</div>

<?php /**PATH C:\Users\VCTUS\Documents\rid\27\app\resources\views/livewire/product-list-page.blade.php ENDPATH**/ ?>