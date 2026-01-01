<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Honeypot spam protection middleware.
 *
 * This middleware checks for honeypot fields that should remain empty.
 * Bots often fill all form fields, including hidden ones.
 * If a honeypot field is filled, the request is rejected.
 */
class HoneypotProtection
{
    /**
     * Honeypot field names to check.
     * These fields should always be empty (hidden from real users).
     */
    protected array $honeypotFields = [
        'website',      // Common bot-filled field
        'fax',          // Obsolete field bots often fill
        'company_url',  // Another trap field
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only check POST/PUT/PATCH requests
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            return $next($request);
        }

        // Check each honeypot field
        foreach ($this->honeypotFields as $field) {
            if ($request->filled($field)) {
                // Log the spam attempt
                \Illuminate\Support\Facades\Log::warning('Honeypot spam detected', [
                    'ip' => $request->ip(),
                    'field' => $field,
                    'value' => substr((string) $request->input($field), 0, 50),
                    'url' => $request->fullUrl(),
                    'user_agent' => $request->userAgent(),
                ]);

                // Return a generic success to not tip off the bot
                // but don't actually process the request
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Success'], 200);
                }

                return redirect()->back()->with('message', 'Thank you for your submission.');
            }
        }

        return $next($request);
    }
}
