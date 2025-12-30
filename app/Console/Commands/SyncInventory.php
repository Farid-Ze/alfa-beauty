<?php

namespace App\Console\Commands;

use App\Services\InventoryService;
use Illuminate\Console\Command;

/**
 * SyncInventory Command
 * 
 * Synchronizes global product stock with batch inventory totals.
 * Use this to fix any discrepancies between product.stock and batch_inventory.quantity_available
 * 
 * Recommended: Run weekly or after data migration
 */
class SyncInventory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:sync 
                            {product? : Specific product ID to sync, or all if omitted}
                            {--dry-run : Preview changes without applying}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync global product stock with batch inventory totals';

    /**
     * Execute the console command.
     */
    public function handle(InventoryService $inventoryService): int
    {
        $productId = $this->argument('product');
        $dryRun = $this->option('dry-run');

        $this->info("🔄 Syncing product stock with batch inventory...");
        
        if ($dryRun) {
            $this->warn("   [DRY RUN MODE - No changes will be made]");
        }

        if ($productId) {
            $this->info("   Syncing product ID: {$productId}");
        } else {
            $this->info("   Syncing ALL products");
        }

        $this->newLine();

        if ($dryRun) {
            // Just show what would change
            $products = \App\Models\Product::query()
                ->when($productId, fn($q) => $q->where('id', $productId))
                ->get();

            $discrepancies = [];
            
            foreach ($products as $product) {
                $batchTotal = \App\Models\BatchInventory::forProduct($product->id)
                    ->active()
                    ->sum('quantity_available');

                if ($product->stock !== $batchTotal) {
                    $discrepancies[] = [
                        $product->id,
                        $product->name,
                        $product->stock,
                        $batchTotal,
                        $batchTotal - $product->stock,
                    ];
                }
            }

            if (empty($discrepancies)) {
                $this->info("✅ All products are in sync! No discrepancies found.");
                return Command::SUCCESS;
            }

            $this->warn("⚠️ Found " . count($discrepancies) . " discrepancy(ies):");
            $this->table(
                ['Product ID', 'Name', 'Current Stock', 'Batch Total', 'Difference'],
                $discrepancies
            );

            return Command::SUCCESS;
        }

        // Actually sync
        $results = $inventoryService->syncStockWithBatches($productId);

        if (empty($results)) {
            $this->info("✅ All products are in sync! No changes needed.");
            return Command::SUCCESS;
        }

        $this->info("📊 Synced " . count($results) . " product(s):");
        $this->table(
            ['Product ID', 'Name', 'Old Stock', 'New Stock', 'Difference'],
            collect($results)->map(fn($r) => [
                $r['product_id'],
                $r['product_name'],
                $r['old_stock'],
                $r['new_stock'],
                $r['difference'] > 0 ? "+{$r['difference']}" : $r['difference'],
            ])->toArray()
        );

        return Command::SUCCESS;
    }
}
