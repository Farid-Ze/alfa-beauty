<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>429 - {{ __('errors.too_many_requests') }} | Alfa Beauty</title>
    <link rel="icon" type="image/webp" href="{{ asset('images/logo.webp') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, sans-serif; background: #FAFAFA; min-height: 100vh; display: flex; flex-direction: column; }
        .error-header { padding: 1.5rem 2rem; border-bottom: 1px solid #E5E4E2; }
        .error-logo { font-family: 'Instrument Serif', Georgia, serif; font-size: 1.5rem; color: #0A0A0A; text-decoration: none; display: flex; align-items: center; gap: 0.75rem; }
        .error-logo img { height: 32px; width: auto; }
        .error-main { flex: 1; display: flex; align-items: center; justify-content: center; padding: 2rem; }
        .error-container { text-align: center; max-width: 480px; }
        .error-code { font-family: 'Instrument Serif', Georgia, serif; font-size: 8rem; font-weight: 400; color: #0A0A0A; line-height: 1; margin-bottom: 0.5rem; letter-spacing: -0.02em; }
        .error-divider { width: 60px; height: 2px; background: #C9A962; margin: 1.5rem auto; }
        .error-title { font-family: 'Instrument Serif', Georgia, serif; font-size: 1.75rem; font-weight: 400; color: #0A0A0A; margin-bottom: 1rem; }
        .error-message { color: #6B7280; font-size: 0.9375rem; line-height: 1.6; margin-bottom: 2rem; }
        .error-actions { display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap; }
        .btn { padding: 0.75rem 1.5rem; border-radius: 4px; text-decoration: none; font-size: 0.875rem; font-weight: 500; transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-primary { background: #0A0A0A; color: #FFFFFF; }
        .btn-primary:hover { background: #1a1a1a; }
        .btn-secondary { background: transparent; color: #0A0A0A; border: 1px solid #E5E4E2; }
        .btn-secondary:hover { border-color: #C9A962; color: #C9A962; }
        .error-footer { padding: 1.5rem 2rem; border-top: 1px solid #E5E4E2; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
        .footer-links { display: flex; gap: 1.5rem; }
        .footer-links a { color: #6B7280; text-decoration: none; font-size: 0.8125rem; transition: color 0.2s; }
        .footer-links a:hover { color: #C9A962; }
        .footer-copy { color: #9CA3AF; font-size: 0.75rem; }
        .countdown { margin-top: 1rem; color: #C9A962; font-size: 0.875rem; }
        @media (max-width: 640px) { .error-code { font-size: 5rem; } .error-title { font-size: 1.25rem; } .error-footer { flex-direction: column; text-align: center; } }
    </style>
</head>
<body>
    <header class="error-header">
        <a href="{{ url('/') }}" class="error-logo">
            <img src="{{ asset('images/logo.webp') }}" alt="Alfa Beauty">
        </a>
    </header>

    <main class="error-main">
        <div class="error-container">
            <div class="error-code">429</div>
            <div class="error-divider"></div>
            <h1 class="error-title">{{ __('errors.too_many_requests') }}</h1>
            <p class="error-message">{{ __('errors.too_many_requests_desc') }}</p>
            <p class="countdown">{{ __('errors.please_wait') }}</p>
            <div class="error-actions">
                <a href="javascript:location.reload()" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M23 4v6h-6M1 20v-6h6"/>
                        <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                    </svg>
                    {{ __('errors.try_again') }}
                </a>
                <a href="{{ url('/') }}" class="btn btn-secondary">{{ __('errors.back_home') }}</a>
            </div>
        </div>
    </main>

    <footer class="error-footer">
        <div class="footer-links">
            <a href="{{ route('products.index') }}">{{ __('nav.products') }}</a>
            <a href="https://wa.me/{{ config('services.whatsapp.business_number') }}" target="_blank">WhatsApp</a>
        </div>
        <span class="footer-copy">© {{ date('Y') }} Alfa Beauty. All rights reserved.</span>
    </footer>
</body>
</html>
