<div>
    <!-- Page Hero -->
    <section class="page-hero">
        <h1>{{ __('checkout.checkout') }}</h1>
        <p>{{ __('checkout.order_summary') }}</p>
    </section>

    <div class="checkout-container">
        <div class="checkout-layout">
            <!-- Shipping Form -->
            <div class="checkout-form">
                <h2 class="checkout-section-title">{{ __('checkout.customer_info') }}</h2>
                
                <form id="checkout-form">
                    <div class="form-group">
                        <label for="name" class="form-label">{{ __('checkout.name') }}</label>
                        <input type="text" id="name" wire:model="name" class="form-input @error('name') error @enderror">
                        @error('name') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label for="phone" class="form-label">{{ __('checkout.phone') }}</label>
                        <input type="text" id="phone" wire:model="phone" placeholder="e.g. 08123456789" class="form-input @error('phone') error @enderror">
                        @error('phone') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label for="address" class="form-label">{{ __('checkout.address') }}</label>
                        <textarea id="address" wire:model="address" rows="3" placeholder="{{ __('checkout.address_placeholder') }}" class="form-input @error('address') error @enderror"></textarea>
                        @error('address') <span class="form-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label for="notes" class="form-label">{{ __('checkout.notes') }}</label>
                        <textarea id="notes" wire:model="notes" rows="2" placeholder="{{ __('checkout.notes_placeholder') }}" class="form-input"></textarea>
                    </div>

                    <!-- WhatsApp Checkout - Primary Action -->
                    <button type="button" wire:click="checkoutViaWhatsApp" wire:loading.attr="disabled" class="btn btn-whatsapp btn-block">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 8px;">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        <span wire:loading.remove>{{ __('checkout.checkout_whatsapp') }}</span>
                        <span wire:loading>{{ __('general.loading') }}...</span>
                    </button>

                    <!-- Divider -->
                    <div class="checkout-divider">
                        <span>{{ __('checkout.or') }}</span>
                    </div>

                    <!-- Standard Checkout - Secondary -->
                    <button type="button" wire:click="placeOrder" wire:loading.attr="disabled" class="btn btn-secondary btn-block">
                        <span wire:loading.remove>{{ __('checkout.place_order') }}</span>
                        <span wire:loading>{{ __('general.loading') }}...</span>
                    </button>
                </form>
            </div>

            <!-- Order Summary -->
            <div class="checkout-summary">
                <h2 class="checkout-summary-title">{{ __('checkout.order_summary') }}</h2>
                <div class="summary-items">
                    @foreach($cartItems as $item)
                        <div class="summary-item">
                            <div class="summary-img">
                                <img src="{{ isset($item->product->images[0]) ? url('storage/' . $item->product->images[0]) : asset('images/product-color.png') }}" alt="{{ $item->product->name }}">
                                <span class="summary-qty">{{ $item->quantity }}</span>
                            </div>
                            <div class="summary-details">
                                <p class="summary-item-name">{{ $item->product->name }}</p>
                                <p class="summary-item-price">Rp {{ number_format($item->product->base_price * $item->quantity, 0, ',', '.') }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="summary-totals">
                    <div class="summary-row">
                        <span class="summary-row-label">{{ __('checkout.subtotal') }}</span>
                        <span>Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-row-label">{{ __('checkout.shipping') }}</span>
                        @auth
                            @if(Auth::user()->loyaltyTier?->free_shipping)
                                <span class="summary-row-shipping-free">{{ __('checkout.free_shipping') }} ({{ Auth::user()->loyaltyTier->name }})</span>
                            @else
                                <span class="summary-row-shipping-free">{{ __('checkout.free_shipping') }}</span>
                            @endif
                        @else
                            <span class="summary-row-shipping-free">{{ __('checkout.free_shipping') }}</span>
                        @endauth
                    </div>

                    @auth
                        @php
                            // Null-safe tier access
                            $tier = Auth::user()->loyaltyTier;
                            $discountPercent = $tier?->discount_percent ?? 0;
                            $multiplier = $tier?->point_multiplier ?? 1.0;
                            
                            // Calculate discount
                            $discountAmount = $subtotal * ($discountPercent / 100);
                            $finalTotal = $subtotal - $discountAmount;
                            
                            // Calculate potential points
                            $potentialPoints = floor(($finalTotal / 10000) * $multiplier);
                        @endphp
                        
                        @if($discountPercent > 0)
                            <div class="summary-row">
                                <span class="summary-row-label">{{ __('checkout.tier_discount', ['tier' => $tier->name, 'percent' => $discountPercent]) }}</span>
                                <span style="color: var(--green);">-Rp {{ number_format($discountAmount, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        
                        @if($potentialPoints > 0)
                            <div class="summary-row summary-row-points">
                                <span>{{ __('checkout.points_earned') }}</span>
                                <span class="summary-row-points-value">+{{ number_format($potentialPoints) }} {{ __('general.pts') }}</span>
                            </div>
                        @endif
                    @endauth
                    
                    <div class="summary-total">
                        <div class="summary-row">
                            <span>{{ __('checkout.total') }}</span>
                            @auth
                                @if(isset($discountAmount) && $discountAmount > 0)
                                    <span>Rp {{ number_format($finalTotal, 0, ',', '.') }}</span>
                                @else
                                    <span>Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                                @endif
                            @else
                                <span>Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


