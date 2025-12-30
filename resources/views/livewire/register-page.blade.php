<div>
    <!-- Page Hero -->
    <section class="page-hero">
        <h1>{{ __('auth.register') }}</h1>
        <p>{{ __('auth.register_subtitle') }}</p>
    </section>

    <div class="form-container wide">
        <div class="form-card">
            <!-- Brand Section -->
            <div class="form-brand">
                <div class="form-brand-logo">{{ __('auth.register_title') }}</div>
                <p class="form-brand-tagline">{{ __('auth.register_subtitle') }}</p>
            </div>

            <form wire:submit.prevent="register">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">{{ __('auth.name') }}</label>
                        <input type="text" wire:model="name" placeholder="John Doe" class="form-input @error('name') error @enderror">
                        @error('name') <span class="form-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('auth.company_name') }}</label>
                        <input type="text" wire:model="company_name" placeholder="Salon Cantik Bunda" class="form-input @error('company_name') error @enderror">
                        @error('company_name') <span class="form-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">{{ __('auth.email') }}</label>
                        <input type="email" wire:model="email" placeholder="you@salon.com" class="form-input @error('email') error @enderror">
                        @error('email') <span class="form-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('auth.phone') }}</label>
                        <input type="text" wire:model="phone" placeholder="08123456789" class="form-input @error('phone') error @enderror">
                        @error('phone') <span class="form-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">{{ __('auth.password') }}</label>
                        <input type="password" wire:model="password" placeholder="Min. 8 characters" class="form-input @error('password') error @enderror">
                        @error('password') <span class="form-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('auth.confirm_password') }}</label>
                        <input type="password" wire:model="password_confirmation" placeholder="{{ __('auth.confirm_password') }}" class="form-input">
                    </div>
                </div>

                <button type="submit" class="btn btn-block btn-lg">{{ __('auth.create_account') }}</button>
                
                <div class="form-footer">
                    {{ __('auth.already_have_account') }} <a href="{{ route('login') }}">{{ __('auth.login_instead') }}</a>
                </div>
            </form>

            <!-- Trust Elements -->
            <div class="form-trust">
                <div class="form-trust-item">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>NIB Verified</span>
                </div>
                <div class="form-trust-item">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span>Wholesale Prices</span>
                </div>
                <div class="form-trust-item">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                    </svg>
                    <span>{{ __('general.points') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>


