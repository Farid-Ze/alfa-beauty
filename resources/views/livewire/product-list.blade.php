<div class="products-grid">
    @foreach($products as $product)
        <livewire:product-card 
            :product="$product" 
            :price-info="$prices[$product->id] ?? null" 
            :key="$product->id" 
        />
    @endforeach
</div>
