<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Add API version headers to responses.
 * 
 * This middleware adds versioning information to API responses
 * for proper client compatibility tracking.
 */
class ApiVersion
{
    /**
     * Current API version.
     */
    public const VERSION = 'v1';

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Add API version headers
        $response->headers->set('X-API-Version', self::VERSION);
        $response->headers->set('X-API-Deprecated', 'false');

        return $response;
    }
}
