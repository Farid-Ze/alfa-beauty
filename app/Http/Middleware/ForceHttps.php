<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Force HTTPS redirect for all requests in production.
 *
 * This middleware ensures all HTTP requests are redirected to HTTPS.
 * Works correctly behind reverse proxies (Vercel, Cloudflare, etc.)
 * by checking the X-Forwarded-Proto header.
 */
class ForceHttps
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip in non-production environments
        if (!app()->environment('production')) {
            return $next($request);
        }

        // Check if request is not secure (handles proxy headers automatically)
        if (!$request->secure()) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
