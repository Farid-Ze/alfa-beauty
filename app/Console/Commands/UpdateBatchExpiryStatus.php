<?php

namespace App\Console\Commands;

use App\Models\BatchInventory;
use Illuminate\Console\Command;
use Carbon\Carbon;

/**
 * UpdateBatchExpiryStatus Command
 * 
 * Updates is_near_expiry and is_expired flags on all batches.
 * Important for accurate inventory reporting and near-expiry discount automation.
 * 
 * Recommended: Run daily
 */
class UpdateBatchExpiryStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:update-expiry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update batch inventory expiry status flags';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("🔄 Updating batch expiry statuses...");

        $today = Carbon::today();
        $nearExpiryThreshold = $today->copy()->addDays(BatchInventory::NEAR_EXPIRY_THRESHOLD_DAYS);

        // Update all batches in one query
        $expiredCount = BatchInventory::where('expires_at', '<', $today)
            ->where('is_expired', false)
            ->update(['is_expired' => true, 'is_active' => false]);

        $nearExpiryCount = BatchInventory::where('expires_at', '>=', $today)
            ->where('expires_at', '<=', $nearExpiryThreshold)
            ->where('is_near_expiry', false)
            ->where('is_expired', false)
            ->update(['is_near_expiry' => true]);

        // Find batches no longer near expiry (in case threshold changed)
        $notNearExpiryCount = BatchInventory::where('expires_at', '>', $nearExpiryThreshold)
            ->where('is_near_expiry', true)
            ->update(['is_near_expiry' => false]);

        $this->info("📊 Expiry Status Update Complete:");
        $this->info("   ⏰ Newly expired batches: {$expiredCount}");
        $this->info("   ⚠️ Newly near-expiry batches: {$nearExpiryCount}");
        $this->info("   ✅ No longer near-expiry: {$notNearExpiryCount}");

        // Summary statistics
        $stats = BatchInventory::selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN is_expired = true THEN 1 ELSE 0 END) as expired,
            SUM(CASE WHEN is_near_expiry = true AND is_expired = false THEN 1 ELSE 0 END) as near_expiry,
            SUM(CASE WHEN is_active = true THEN 1 ELSE 0 END) as active
        ')->first();

        $this->newLine();
        $this->info("📦 Current Batch Inventory Summary:");
        $this->table(
            ['Total Batches', 'Active', 'Near Expiry', 'Expired'],
            [[$stats->total, $stats->active, $stats->near_expiry, $stats->expired]]
        );

        return Command::SUCCESS;
    }
}
