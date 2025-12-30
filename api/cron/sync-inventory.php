<?php

/**
 * Cron Job: Sync Inventory
 * 
 * Called by Vercel Cron at 03:00 daily to synchronize inventory
 * between batch levels and global product stock.
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
    $exitCode = Illuminate\Support\Facades\Artisan::call('inventory:sync');
    
    echo json_encode([
        'success' => true,
        'command' => 'inventory:sync',
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
