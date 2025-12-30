<?php

/**
 * Cron Job: Update Expiry Status
 * 
 * Called by Vercel Cron at 02:00 daily to update near-expiry
 * flags on batch inventory items.
 */

require __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

// Verify this is a cron request (optional security)
$cronSecret = $_SERVER['CRON_SECRET'] ?? '';
$expectedSecret = env('CRON_SECRET', '');

if ($expectedSecret && $cronSecret !== $expectedSecret) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $exitCode = Illuminate\Support\Facades\Artisan::call('inventory:update-expiry');
    
    echo json_encode([
        'success' => true,
        'command' => 'inventory:update-expiry',
        'exit_code' => $exitCode,
        'timestamp' => date('c'),
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
