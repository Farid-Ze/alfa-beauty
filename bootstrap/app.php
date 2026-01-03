<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Vercel Serverless Configuration
 * 
 * Vercel has a read-only filesystem except /tmp.
 * We need to configure Laravel to use /tmp for all writable paths
 * BEFORE the Application is created.
 */
if (getenv('VERCEL')) {
    // Set cache/compiled paths to /tmp for serverless
    $_ENV['VIEW_COMPILED_PATH'] = '/tmp/views';
    $_ENV['APP_SERVICES_CACHE'] = '/tmp/services.php';
    $_ENV['APP_PACKAGES_CACHE'] = '/tmp/packages.php';
    $_ENV['APP_CONFIG_CACHE'] = '/tmp/config.php';
    $_ENV['APP_ROUTES_CACHE'] = '/tmp/routes.php';
    $_ENV['APP_EVENTS_CACHE'] = '/tmp/events.php';
    
    // Ensure /tmp directories exist
    $tmpDirs = ['/tmp/views', '/tmp/storage', '/tmp/storage/framework', '/tmp/storage/framework/views'];
    foreach ($tmpDirs as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
    }
    
    // Set fallback environment variables for serverless-safe drivers
    if (!getenv('CACHE_STORE')) {
        putenv('CACHE_STORE=array');
    }
    if (!getenv('SESSION_DRIVER')) {
        // IMPORTANT: Use database sessions, NOT cookie
        // Cookie sessions have 4KB limit which causes session data loss
        // manifesting as 'Guest' display despite being logged in
        putenv('SESSION_DRIVER=database');
    }
    if (!getenv('LOG_CHANNEL')) {
        putenv('LOG_CHANNEL=stderr');
    }
}

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust all proxies (Vercel, Cloudflare, etc.) - fixes HTTPS detection
        $middleware->trustProxies(
            at: '*',
            headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
                     \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
                     \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
                     \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO |
                     \Illuminate\Http\Request::HEADER_X_FORWARDED_AWS_ELB
        );
        
        // Global middleware for all requests
        $middleware->append(\App\Http\Middleware\RequestCorrelation::class);
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->append(\App\Http\Middleware\ForceHttps::class);
        
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\HoneypotProtection::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Don't report 404s
        $exceptions->dontReport([
            NotFoundHttpException::class,
        ]);
    })
    ->create();

// Use /tmp for storage in serverless (Vercel has read-only filesystem)
if (getenv('VERCEL')) {
    $app->useStoragePath('/tmp/storage');
}

return $app;
