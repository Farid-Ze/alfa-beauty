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
            <h2>{{ __('cart.shopping_cart') }} <span class="cart-count">({{ $items->sum('quantity') }})</span></h2>
            <button class="close-btn" @click="open = false" aria-label="{{ __('general.close') }}">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="cart-items">
            @forelse($items as $item)
                <div class="cart-item" wire:key="cart-item-{{ $item->id }}">
                    <!-- Image -->
                    <div class="cart-item-image">
                        @if($item->product->images && count($item->product->images) > 0)
                            <img src="{{ url('storage/' . $item->product->images[0]) }}" alt="{{ $item->product->name }}">
                        @else
                            <div class="cart-item-placeholder">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        @endif
                    </div>
                    
                    <!-- Content -->
                    <div class="cart-item-content">
                        <h4 class="cart-item-name">{{ $item->product->name }}</h4>
                        <div class="cart-item-meta">
                            <div class="cart-item-pricing">
                                <span class="cart-item-price">Rp {{ number_format($item->product->price * $item->quantity, 0, ',', '.') }}</span>
                                <span class="cart-item-pts">+{{ $item->product->points * $item->quantity }} {{ __('general.pts') }}</span>
                            </div>
                            <div class="cart-item-actions">
                                <div class="cart-item-qty">
                                    <button 
                                        wire:click="updateQuantity({{ $item->id }}, {{ $item->quantity - 1 }})"
                                        wire:loading.attr="disabled"
                                        wire:target="updateQuantity"
                                        {{ $item->quantity <= 1 ? 'disabled' : '' }}
                                    >−</button>
                                    <span>{{ $item->quantity }}</span>
                                    <button 
                                        wire:click="updateQuantity({{ $item->id }}, {{ $item->quantity + 1 }})"
                                        wire:loading.attr="disabled"
                                        wire:target="updateQuantity"
                                    >+</button>
                                </div>
                                <button 
                                    class="cart-item-remove" 
                                    wire:click="removeItem({{ $item->id }})"
                                    wire:loading.class="opacity-50"
                                    wire:target="removeItem({{ $item->id }})"
                                >{{ __('cart.remove') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="cart-empty">
                    <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    <p>{{ __('cart.cart_empty') }}</p>
                    <a href="/products" class="btn btn-sm">{{ __('cart.view_products') }}</a>
                </div>
            @endforelse
        </div>

        @if($items->count() > 0)
        @php
            $totalPoints = $items->sum(fn($item) => $item->product->points * $item->quantity);
        @endphp
        <div class="cart-footer">
            <div class="cart-subtotal">
                <div class="subtotal-left">
                    <span class="subtotal-label">{{ __('cart.subtotal') }}</span>
                </div>
                <div class="subtotal-right">
                    <span class="subtotal-amount">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                    <span class="subtotal-pts">+{{ $totalPoints }} {{ __('general.pts') }}</span>
                </div>
            </div>

            <a href="/checkout" class="btn btn-block">{{ __('cart.checkout') }}</a>
        </div>
        @endif
    </aside>
</div>

