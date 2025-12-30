<?php

namespace App\Services;

use App\Models\BatchInventory;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * InventoryService
 * 
 * Handles batch-level inventory management with FEFO (First Expired, First Out) algorithm.
 * This is critical for BPOM compliance and proper pharmaceutical/cosmetic inventory tracking.
 * 
 * Algorithm Priority:
 * 1. FEFO (First Expired First Out) - Primary for cosmetics, prevents waste
 * 2. FIFO (First In First Out) - Fallback if no expiry data
 * 3. Near-expiry batches get priority to minimize losses
 */
class InventoryService
{
    /**
     * Default reservation timeout in hours.
     * After this period, unreserved stock can be released.
     */
    const DEFAULT_RESERVATION_TIMEOUT_HOURS = 24;

    /**
     * Allocate stock from batches using FEFO algorithm.
     * 
     * Algorithm:
     * 1. Get all active batches for product, ordered by expiry date (ASC)
     * 2. Prioritize near-expiry batches to reduce waste
     * 3. Allocate from earliest expiring batch first
     * 4. If batch has insufficient stock, continue to next batch
     * 5. Track allocations for traceability
     *
     * @param int $productId Product to allocate from
     * @param int $quantity Quantity to allocate
     * @param string|null $orderId Optional order reference for logging
     * @return array Array of allocations: [['batch_id' => x, 'batch_number' => y, 'quantity' => z, 'expires_at' => date], ...]
     * @throws \Exception If insufficient stock
     */
    public function allocateStock(int $productId, int $quantity, ?string $orderId = null): array
    {
        return DB::transaction(function () use ($productId, $quantity, $orderId) {
            // Get product for validation
            $product = Product::findOrFail($productId);
            
            // Quick check: is global stock sufficient?
            if ($product->stock < $quantity) {
                throw new \Exception(
                    "Insufficient global stock for product '{$product->name}'. " .
                    "Requested: {$quantity}, Available: {$product->stock}"
                );
            }

            // Get available batches using FEFO ordering
            // Priority: Near-expiry first (to reduce waste), then by expiry date
            $batches = BatchInventory::forProduct($productId)
                ->active()
                ->orderByDesc('is_near_expiry') // Near-expiry batches first
                ->orderBy('expires_at', 'asc')   // Then by earliest expiry (FEFO)
                ->orderBy('received_at', 'asc')  // Then by FIFO as tiebreaker
                ->lockForUpdate()
                ->get();

            $totalBatchAvailable = $batches->sum('quantity_available');
            
            // Validate batch-level stock
            if ($totalBatchAvailable < $quantity) {
                // Option 1: Strict mode - fail
                // throw new \Exception("Insufficient batch stock. Global: {$product->stock}, Batch total: {$totalBatchAvailable}");
                
                // Option 2: Graceful mode - log warning and use global stock only
                Log::warning('Batch inventory mismatch detected', [
                    'product_id' => $productId,
                    'product_name' => $product->name,
                    'global_stock' => $product->stock,
                    'batch_total' => $totalBatchAvailable,
                    'requested' => $quantity,
                ]);
                
                // If we have batches but not enough, allocate what we can
                if ($totalBatchAvailable > 0) {
                    $quantity = min($quantity, $totalBatchAvailable);
                } else {
                    // No batches exist, just use global stock (legacy mode)
                    $product->decrement('stock', $quantity);
                    return [[
                        'batch_id' => null,
                        'batch_number' => 'LEGACY-NO-BATCH',
                        'quantity' => $quantity,
                        'expires_at' => null,
                        'mode' => 'legacy',
                    ]];
                }
            }

            $allocations = [];
            $remaining = $quantity;

            foreach ($batches as $batch) {
                if ($remaining <= 0) break;

                $takeFromBatch = min($remaining, $batch->quantity_available);
                
                if ($takeFromBatch <= 0) continue;

                // Atomically update batch
                $batch->update([
                    'quantity_available' => $batch->quantity_available - $takeFromBatch,
                    'quantity_sold' => $batch->quantity_sold + $takeFromBatch,
                ]);

                // Deactivate batch if depleted
                if ($batch->quantity_available <= 0) {
                    $batch->update(['is_active' => false]);
                }

                $allocations[] = [
                    'batch_id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'lot_number' => $batch->lot_number,
                    'quantity' => $takeFromBatch,
                    'expires_at' => $batch->expires_at?->toDateString(),
                    'is_near_expiry' => $batch->is_near_expiry,
                    'cost_price' => $batch->cost_price,
                ];

                $remaining -= $takeFromBatch;

                Log::info('FEFO: Batch stock allocated', [
                    'order_id' => $orderId,
                    'product_id' => $productId,
                    'batch_id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'quantity_allocated' => $takeFromBatch,
                    'batch_remaining' => $batch->quantity_available - $takeFromBatch,
                    'expires_at' => $batch->expires_at?->toDateString(),
                ]);
            }

            // Update global product stock
            $allocatedTotal = collect($allocations)->sum('quantity');
            $product->decrement('stock', $allocatedTotal);

            // Verify allocation matches request
            if ($allocatedTotal < $quantity) {
                Log::error('FEFO allocation incomplete', [
                    'product_id' => $productId,
                    'requested' => $quantity,
                    'allocated' => $allocatedTotal,
                    'shortage' => $quantity - $allocatedTotal,
                ]);
            }

            return $allocations;
        });
    }

    /**
     * Release stock back to batches (for cancelled/refunded orders).
     * 
     * Reverses FEFO allocation by returning stock to original batches.
     *
     * @param array $allocations Original allocations from allocateStock()
     * @param string|null $reason Reason for release (for audit log)
     */
    public function releaseStock(array $allocations, ?string $reason = null): void
    {
        DB::transaction(function () use ($allocations, $reason) {
            foreach ($allocations as $alloc) {
                // Skip legacy/non-batch allocations
                if (empty($alloc['batch_id'])) {
                    if (isset($alloc['product_id'])) {
                        Product::where('id', $alloc['product_id'])->increment('stock', $alloc['quantity']);
                    }
                    continue;
                }

                $batch = BatchInventory::find($alloc['batch_id']);
                
                if (!$batch) {
                    Log::warning('Batch not found for stock release', $alloc);
                    continue;
                }

                $batch->update([
                    'quantity_available' => $batch->quantity_available + $alloc['quantity'],
                    'quantity_sold' => max(0, $batch->quantity_sold - $alloc['quantity']),
                    'is_active' => true, // Reactivate if it was depleted
                ]);

                // Update global product stock
                $batch->product->increment('stock', $alloc['quantity']);

                Log::info('FEFO: Stock released back to batch', [
                    'batch_id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'quantity_released' => $alloc['quantity'],
                    'reason' => $reason,
                ]);
            }
        });
    }

    /**
     * Check if product has sufficient stock across batches.
     *
     * @param int $productId
     * @param int $quantity
     * @return bool
     */
    public function hasAvailableStock(int $productId, int $quantity): bool
    {
        $batchTotal = BatchInventory::forProduct($productId)
            ->active()
            ->sum('quantity_available');

        $globalStock = Product::find($productId)?->stock ?? 0;

        // Use the minimum of batch total and global stock
        return min($batchTotal, $globalStock) >= $quantity;
    }

    /**
     * Get available batches for a product with allocation preview.
     *
     * @param int $productId
     * @param int|null $quantity If provided, shows how allocation would work
     * @return array
     */
    public function getAvailableBatches(int $productId, ?int $quantity = null): array
    {
        $batches = BatchInventory::forProduct($productId)
            ->active()
            ->orderByDesc('is_near_expiry')
            ->orderBy('expires_at', 'asc')
            ->get()
            ->map(function ($batch) {
                return [
                    'id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'quantity_available' => $batch->quantity_available,
                    'expires_at' => $batch->expires_at?->toDateString(),
                    'days_until_expiry' => $batch->days_until_expiry,
                    'is_near_expiry' => $batch->is_near_expiry,
                ];
            });

        $result = [
            'batches' => $batches,
            'total_available' => $batches->sum('quantity_available'),
        ];

        // Preview allocation if quantity provided
        if ($quantity && $quantity > 0) {
            $preview = [];
            $remaining = $quantity;
            
            foreach ($batches as $batch) {
                if ($remaining <= 0) break;
                $take = min($remaining, $batch['quantity_available']);
                $preview[] = [
                    'batch_number' => $batch['batch_number'],
                    'quantity' => $take,
                ];
                $remaining -= $take;
            }
            
            $result['allocation_preview'] = $preview;
            $result['can_fulfill'] = $remaining <= 0;
        }

        return $result;
    }

    /**
     * Sync global product stock with batch totals.
     * 
     * Call this periodically to fix any discrepancies.
     *
     * @param int|null $productId Specific product or null for all
     * @return array Sync results
     */
    public function syncStockWithBatches(?int $productId = null): array
    {
        $query = Product::query();
        if ($productId) {
            $query->where('id', $productId);
        }

        $results = [];

        $query->chunk(100, function ($products) use (&$results) {
            foreach ($products as $product) {
                $batchTotal = BatchInventory::forProduct($product->id)
                    ->active()
                    ->sum('quantity_available');

                if ($product->stock !== $batchTotal) {
                    $oldStock = $product->stock;
                    $product->update(['stock' => $batchTotal]);

                    $results[] = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'old_stock' => $oldStock,
                        'new_stock' => $batchTotal,
                        'difference' => $batchTotal - $oldStock,
                    ];

                    Log::warning('Stock synced with batches', [
                        'product_id' => $product->id,
                        'old_stock' => $oldStock,
                        'batch_total' => $batchTotal,
                    ]);
                }
            }
        });

        return $results;
    }
}
