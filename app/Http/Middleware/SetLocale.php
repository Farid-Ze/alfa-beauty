<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     * Sets the application locale based on session, cookie, or browser preference.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Priority: 1. Session, 2. Cookie, 3. Default (id)
        $locale = session('locale', $request->cookie('locale', config('app.locale')));
        
        // Validate locale
        if (!in_array($locale, ['id', 'en'])) {
            $locale = 'id';
        }
        
        App::setLocale($locale);
        
        return $next($request);
    }
}
