<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    /*
     * Which paths should CORS headers be applied to.
     * Include api/* for all API routes, sanctum/csrf-cookie for SPA auth.
     */
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    /*
     * Allowed HTTP methods for CORS requests.
     */
    'allowed_methods' => ['*'],

    /*
     * Allowed origins for CORS requests.
     * 
     * Development: localhost domains
     * Production: Set via CORS_ALLOWED_ORIGINS env variable
     * 
     * Example production value:
     * CORS_ALLOWED_ORIGINS=https://alfabeauty.id,https://www.alfabeauty.id
     */
    'allowed_origins' => array_filter(
        array_merge(
            ['http://localhost', 'http://localhost:3000', 'http://127.0.0.1:8000'],
            explode(',', env('CORS_ALLOWED_ORIGINS', ''))
        )
    ),

    /*
     * Patterns for allowed origins (supports regex).
     * Useful for allowing multiple subdomains.
     */
    'allowed_origins_patterns' => [
        // Allow all subdomains of alfabeauty.id in production
        // '/^https:\/\/.*\.alfabeauty\.id$/',
    ],

    /*
     * Allowed headers in CORS requests.
     */
    'allowed_headers' => ['*'],

    /*
     * Headers exposed to the browser in the response.
     */
    'exposed_headers' => ['X-API-Version'],

    /*
     * Max age (in seconds) for preflight request caching.
     * 24 hours = 86400 seconds
     */
    'max_age' => 86400,

    /*
     * Whether credentials (cookies, authorization headers) are supported.
     * Required for Sanctum SPA authentication.
     */
    'supports_credentials' => true,

];
