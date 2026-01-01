<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>503 - {{ __('errors.maintenance') }} | Alfa Beauty</title>
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
        .error-icon { width: 64px; height: 64px; margin: 0 auto 1.5rem; color: #C9A962; }
        .error-title { font-family: 'Instrument Serif', Georgia, serif; font-size: 2rem; font-weight: 400; color: #0A0A0A; margin-bottom: 1rem; }
        .error-divider { width: 60px; height: 2px; background: #C9A962; margin: 1.5rem auto; }
        .error-message { color: #6B7280; font-size: 0.9375rem; line-height: 1.6; margin-bottom: 2rem; }
        .error-footer { padding: 1.5rem 2rem; border-top: 1px solid #E5E4E2; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
        .footer-links { display: flex; gap: 1.5rem; }
        .footer-links a { color: #6B7280; text-decoration: none; font-size: 0.8125rem; transition: color 0.2s; }
        .footer-links a:hover { color: #C9A962; }
        .footer-copy { color: #9CA3AF; font-size: 0.75rem; }
        @media (max-width: 640px) { .error-title { font-size: 1.5rem; } .error-footer { flex-direction: column; text-align: center; } }
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
            <svg class="error-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
            <h1 class="error-title">{{ __('errors.maintenance') }}</h1>
            <div class="error-divider"></div>
            <p class="error-message">{{ __('errors.maintenance_desc') }}</p>
        </div>
    </main>

    <footer class="error-footer">
        <div class="footer-links">
            <a href="https://instagram.com/alfabeauty" target="_blank">Instagram</a>
            <a href="https://wa.me/{{ config('services.whatsapp.business_number') }}" target="_blank">WhatsApp</a>
            <a href="mailto:support@alfabeauty.id">Email</a>
        </div>
        <span class="footer-copy">© {{ date('Y') }} Alfa Beauty. All rights reserved.</span>
    </footer>
</body>
</html>
