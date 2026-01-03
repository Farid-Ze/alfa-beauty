<?php

/**
 * Vercel Serverless Entry Point for Laravel 12
 * 
 * Uses Laravel 12's handleRequest() method for proper application bootstrapping.
 * This is the correct pattern that ensures all service providers are registered.
 */

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/**
 * Minimal, safe request correlation even before Laravel boots.
 */
$requestId = $_SERVER['HTTP_X_REQUEST_ID']
    ?? $_SERVER['HTTP_X_VERCEL_ID']
    ?? $_SERVER['HTTP_X_AMZN_TRACE_ID']
    ?? bin2hex(random_bytes(16));

if (!headers_sent()) {
    header('X-Request-Id: ' . $requestId);
}

/**
 * Ensure writable storage exists on Vercel (read-only FS except /tmp).
 */
function ensureVercelStorage(): void
{
    if (!getenv('VERCEL')) {
        return;
    }

    $base = '/tmp/storage';
    $paths = [
        $base,
        $base . '/app',
        $base . '/framework',
        $base . '/framework/cache',
        $base . '/framework/cache/data',
        $base . '/framework/sessions',
        $base . '/framework/views',
        $base . '/logs',
    ];

    foreach ($paths as $path) {
        if (!is_dir($path)) {
            @mkdir($path, 0777, true);
        }
    }
}

/**
 * Emit safe 500 response and log the underlying cause to stderr.
 */
function failBoot(string $requestId, string $reason, ?\Throwable $e = null): never
{
    $context = [
        'request_id' => $requestId,
        'reason' => $reason,
        'path' => $_SERVER['REQUEST_URI'] ?? null,
        'method' => $_SERVER['REQUEST_METHOD'] ?? null,
        'host' => $_SERVER['HTTP_HOST'] ?? null,
        'vercel_id' => $_SERVER['HTTP_X_VERCEL_ID'] ?? null,
    ];

    if ($e) {
        $context['error'] = $e->getMessage();
        $context['exception'] = get_class($e);
        $context['file'] = $e->getFile();
        $context['line'] = $e->getLine();
    }

    error_log('[vercel][bootstrap] ' . json_encode($context));

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
    }

    echo "Server error. request_id={$requestId}";
    exit(0);
}

// Set up error handlers
set_exception_handler(function (\Throwable $e) use ($requestId): void {
    failBoot($requestId, 'uncaught_exception', $e);
});

register_shutdown_function(function () use ($requestId): void {
    $err = error_get_last();
    if (!$err) {
        return;
    }

    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
    if (!in_array($err['type'] ?? 0, $fatalTypes, true)) {
        return;
    }

    failBoot($requestId, 'fatal_error:' . ($err['type'] ?? 0), new \ErrorException(
        $err['message'] ?? 'fatal',
        0,
        $err['type'] ?? 1,
        $err['file'] ?? __FILE__,
        $err['line'] ?? 0,
    ));
});

// Ensure storage directories exist for Vercel
ensureVercelStorage();

// Common root-cause: missing APP_KEY in production environment.
if (!getenv('APP_KEY')) {
    failBoot($requestId, 'missing_app_key');
}

// Register the Composer autoloader
require __DIR__ . '/../vendor/autoload.php';

// Determine if the application is in maintenance mode
if (file_exists($maintenance = __DIR__ . '/../storage/framework/maintenance.php')) {
    require $maintenance;
}

try {
    // Bootstrap Laravel and handle the request using Laravel 12's proper method
    /** @var Application $app */
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    
    // Capture request and inject request_id for traceability
    $request = Request::capture();
    $request->attributes->set('request_id', $requestId);
    
    // Use Laravel 12's handleRequest() which properly bootstraps all providers
    $app->handleRequest($request);
    
} catch (\Throwable $e) {
    failBoot($requestId, 'app_handle_failed', $e);
}
