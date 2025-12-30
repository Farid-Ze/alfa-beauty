<div class="products-grid">
    @foreach($products as $product)
        <livewire:product-card :product="$product" :key="$product->id" />
    @endforeach
</div>
