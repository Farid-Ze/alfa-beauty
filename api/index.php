<?php

/**
 * Vercel Serverless Entry Point for Laravel
 * 
 * With extensive error handling for debugging
 */

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log handler for fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'Fatal Error',
            'message' => $error['message'],
            'file' => $error['file'],
            'line' => $error['line']
        ]);
    }
});

try {
    // Get request URI
    $uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
    
    // Define base path
    $basePath = realpath(__DIR__ . '/..');
    
    if (!$basePath) {
        throw new Exception('Cannot resolve base path from: ' . __DIR__);
    }
    
    // Check if autoload exists
    $autoloadPath = $basePath . '/vendor/autoload.php';
    if (!file_exists($autoloadPath)) {
        throw new Exception("Autoload not found at: $autoloadPath. CWD: " . getcwd() . ". Contents: " . implode(', ', scandir($basePath)));
    }
    
    // Handle static files from public folder
    $publicPath = $basePath . '/public' . $uri;
    if ($uri !== '/' && file_exists($publicPath) && is_file($publicPath)) {
        $extension = pathinfo($publicPath, PATHINFO_EXTENSION);
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
        ];
        
        if (isset($mimeTypes[$extension])) {
            header('Content-Type: ' . $mimeTypes[$extension]);
        }
        
        readfile($publicPath);
        return;
    }
    
    // Change working directory
    chdir($basePath);
    
    // Ensure storage directories exist and are writable
    $storageDirs = [
        $basePath . '/storage/framework/sessions',
        $basePath . '/storage/framework/views',
        $basePath . '/storage/framework/cache',
        $basePath . '/storage/logs',
        $basePath . '/bootstrap/cache',
    ];
    
    foreach ($storageDirs as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }
    
    // Load autoloader
    require $autoloadPath;
    
    // Bootstrap Laravel
    $app = require_once $basePath . '/bootstrap/app.php';
    
    // Get HTTP kernel
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    
    // Handle request
    $response = $kernel->handle(
        $request = Illuminate\Http\Request::capture()
    );
    
    // Send response
    $response->send();
    
    // Terminate
    $kernel->terminate($request, $response);
    
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => get_class($e),
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => array_slice($e->getTrace(), 0, 5)
    ], JSON_PRETTY_PRINT);
}
