<?php
/**
 * Clear application cache - Protected endpoint
 * Access: /api/cache-clear?key=CACHE_CLEAR_SECRET
 */

// Only allow if secret key matches
$secretKey = getenv('CACHE_CLEAR_SECRET');
$secretKey = is_string($secretKey) ? trim($secretKey) : '';
$providedKey = $_GET['key'] ?? '';

if ($secretKey === '') {
    http_response_code(500);
    die(json_encode(['error' => 'CACHE_CLEAR_SECRET is not configured']));
}

if ($providedKey !== $secretKey) {
    http_response_code(403);
    die(json_encode(['error' => 'Forbidden']));
}

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    // Clear application cache
    Illuminate\Support\Facades\Cache::flush();
    
    // Also clear cache table directly via DB
    Illuminate\Support\Facades\DB::table('cache')->truncate();
    Illuminate\Support\Facades\DB::table('cache_locks')->truncate();
    
    echo json_encode([
        'success' => true,
        'message' => 'Cache cleared successfully',
        'timestamp' => now()->toISOString(),
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
    ]);
}
