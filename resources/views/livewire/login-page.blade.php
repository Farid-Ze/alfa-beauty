<div>
    <!-- Page Hero -->
    <section class="page-hero">
        <h1>{{ __('auth.login') }}</h1>
        <p>{{ __('auth.login_subtitle') }}</p>
    </section>

    <div class="form-container">
        <div class="form-card">
            <!-- Brand Section -->
            <div class="form-brand">
                <div class="form-brand-logo">Alfa Beauty</div>
                <p class="form-brand-tagline">{{ __('nav.professional_hair_care') }}</p>
            </div>

            <form wire:submit.prevent="login">
                <div class="form-group">
                    <label class="form-label">{{ __('auth.email') }}</label>
                    <input type="email" wire:model="email" placeholder="you@salon.com" class="form-input @error('email') error @enderror">
                    @error('email') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">{{ __('auth.password') }}</label>
                    <input type="password" wire:model="password" placeholder="••••••••" class="form-input @error('password') error @enderror">
                    @error('password') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="btn btn-block btn-lg">{{ __('auth.login') }}</button>
                
                <div class="form-footer">
                    {{ __('auth.no_account') }} <a href="{{ route('register') }}">{{ __('auth.register_now') }}</a>
                </div>
            </form>

            <!-- Trust Elements -->
            <div class="form-trust">
                <div class="form-trust-item">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <span>Secure Login</span>
                </div>
                <div class="form-trust-item">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <span>Verified Partner</span>
                </div>
            </div>
        </div>
    </div>
</div>


