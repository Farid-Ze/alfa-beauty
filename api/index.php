<?php

/**
 * Vercel Serverless Entry Point for Laravel
 * Simplified production-ready version
 */

declare(strict_types=1);

define('LARAVEL_START', microtime(true));

/**
 * Minimal, safe request correlation even before Laravel boots.
 * - Mirrors our in-app request correlation, but works for early bootstrap failures.
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
 * Laravel will use /tmp/storage when VERCEL is set (see bootstrap/app.php).
 */
function ensureVercelStorage(string $requestId): void
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
        $base . '/framework/sessions',
        $base . '/framework/views',
        $base . '/logs',
    ];

    foreach ($paths as $path) {
        if (is_dir($path)) {
            continue;
        }

        if (!@mkdir($path, 0777, true) && !is_dir($path)) {
            error_log("[vercel][storage] failed mkdir {$path} request_id={$requestId}");
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

ensureVercelStorage($requestId);

// Common root-cause: missing APP_KEY in production environment.
// Keep response generic, but log a helpful reason to stderr.
if (!getenv('APP_KEY')) {
    failBoot($requestId, 'missing_app_key');
}

require __DIR__ . '/../vendor/autoload.php';

try {
    $app = require_once __DIR__ . '/../bootstrap/app.php';
} catch (\Throwable $e) {
    failBoot($requestId, 'bootstrap_app_failed', $e);
}

try {
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
} catch (\Throwable $e) {
    failBoot($requestId, 'make_http_kernel_failed', $e);
}

// VERCEL FIX: Laravel 12 serverless requires explicit provider registration
// The Application::configure() pattern may not fully register providers on cold start
try {
    // Ensure config is loaded
    $app->make('config');
    
    // Force register core framework providers if not already registered
    if (!$app->bound('view')) {
        $app->register(\Illuminate\View\ViewServiceProvider::class);
    }
    if (!$app->bound('files')) {
        $app->register(\Illuminate\Filesystem\FilesystemServiceProvider::class);
    }
    if (!$app->bound('session')) {
        $app->register(\Illuminate\Session\SessionServiceProvider::class);
    }
} catch (\Throwable $e) {
    failBoot($requestId, 'provider_registration_failed', $e);
}

try {
    $request = Illuminate\Http\Request::capture();

    // Ensure Laravel sees a request_id even if middleware is bypassed due to early exceptions.
    $request->attributes->set('request_id', $requestId);

    $response = $kernel->handle($request);
} catch (\Throwable $e) {
    failBoot($requestId, 'kernel_handle_failed', $e);
}

$response->send();

$kernel->terminate($request, $response);
