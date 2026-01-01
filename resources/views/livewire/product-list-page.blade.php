<div>
    <!-- Page Hero -->
    <section class="page-hero">
        <h1 class="heading-massive">{{ __('products.catalog') }}</h1>
        <p>{{ __('products.catalog_subtitle') }}</p>
    </section>

    <!-- Mobile Filter Toggle -->
    <button 
        class="mobile-filter-toggle" 
        x-data
        x-on:click="document.querySelector('.sidebar').classList.toggle('active')"
    >
        ☰ {{ __('products.filter_products') }}
    </button>

    <!-- Content Area -->
    <section class="content-grid">
        
        <!-- Sidebar Filters -->
        <aside class="sidebar">
            
            <!-- Search -->
            <div class="sidebar-section">
                <label class="sidebar-label">{{ __('general.search') }}</label>
                <input 
                    wire:model.live.debounce.300ms="search" 
                    type="text" 
                    placeholder="{{ __('products.search_placeholder') }}" 
                    class="sidebar-input"
                >
            </div>

            <!-- Categories -->
            <div class="sidebar-section">
                <label class="sidebar-label">{{ __('products.category') }}</label>
                <div class="filter-list">
                    @foreach($categories as $category)
                        <label class="filter-item">
                            <input wire:model.live="selectedCategories" type="checkbox" value="{{ $category->id }}">
                            <span>{{ $category->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Brands -->
            <div class="sidebar-section">
                <label class="sidebar-label">{{ __('products.brand') }}</label>
                <div class="filter-list">
                    @foreach($brands as $brand)
                        <label class="filter-item">
                            <input wire:model.live="selectedBrands" type="checkbox" value="{{ $brand->id }}">
                            <span>{{ $brand->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Price Range -->
            <div class="sidebar-section">
                <label class="sidebar-label">{{ __('products.price_range') }}</label>
                <div class="price-range-inputs">
                    <input wire:model.live.debounce.500ms="priceMin" type="number" placeholder="{{ __('products.min_price') }}" class="sidebar-input">
                    <span style="color: var(--gray-400);">–</span>
                    <input wire:model.live.debounce.500ms="priceMax" type="number" placeholder="{{ __('products.max_price') }}" class="sidebar-input">
                </div>
            </div>

            <!-- Clear Filters -->
            @if(!empty($selectedCategories) || !empty($selectedBrands) || $priceMin || $priceMax || $search)
                <button 
                    wire:click="clearFilters" 
                    class="clear-filters-btn"
                >
                    {{ __('products.clear_filters') }}
                </button>
            @endif
        </aside>

        <!-- Products Grid -->
        <div>
            <!-- Toolbar -->
            <div class="toolbar">
                <p class="toolbar-info">
                    {{ __('products.showing_results', ['count' => $products->total()]) }}
                </p>
                <div class="toolbar-sort">
                    <label>{{ __('products.sort_by') }}:</label>
                    <select wire:model.live="sort">
                        <option value="latest">{{ __('products.latest') }}</option>
                        <option value="price_asc">{{ __('products.price_low_high') }}</option>
                        <option value="price_desc">{{ __('products.price_high_low') }}</option>
                        <option value="name_asc">{{ __('products.name_a_z') }}</option>
                        <option value="name_desc">{{ __('products.name_z_a') }}</option>
                    </select>
                </div>
            </div>

            <!-- Grid -->
            <div wire:loading.class="opacity-50" class="products-grid">
                @forelse($products as $product)
                    <livewire:product-card 
                        :product="$product" 
                        :price-info="$prices[$product->id] ?? null" 
                        :key="$product->id" 
                    />
                @empty
                    <div class="no-products-message">
                        <p style="font-family: var(--font-display); font-size: 1.5rem; margin-bottom: var(--space-sm);">{{ __('products.no_products') }}</p>
                        <p style="font-size: 0.875rem;">{{ __('products.try_different_filter') }}</p>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            <div class="pagination-container">
                {{ $products->links() }}
            </div>
        </div>
    </section>
</div>

