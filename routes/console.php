<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
|
| Define console commands that should be scheduled to run periodically.
| These tasks maintain data integrity and clean up stale records.
|
*/

// Clean up orphaned WhatsApp orders every hour
// Orders not confirmed within 24 hours are automatically cancelled
Schedule::command('orders:cleanup-orphaned --hours=24')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/orphaned-orders-cleanup.log'));

// Sync inventory daily at 3 AM (low traffic)
// Fixes any discrepancies between product.stock and batch_inventory
Schedule::command('inventory:sync')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/inventory-sync.log'));

// Update batch expiry status daily at 2 AM
// Marks near-expiry and expired batches for reporting and discount automation
Schedule::command('inventory:update-expiry')
    ->dailyAt('02:00')
    ->appendOutputTo(storage_path('logs/batch-expiry-update.log'));
