<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\PaymentLog;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CleanupOrphanedOrders Command
 * 
 * Automatically cancels WhatsApp orders that were not confirmed within the timeout period.
 * This releases held stock back to inventory and keeps reports accurate.
 * 
 * Algorithm:
 * 1. Find all WhatsApp orders with pending_payment status older than X hours
 * 2. For each order, release stock back to batches using InventoryService
 * 3. Update order status to cancelled with auto-cancel note
 * 4. Log all cancellations for audit
 * 
 * Recommended schedule: Run hourly
 */
class CleanupOrphanedOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:cleanup-orphaned 
                            {--hours=24 : Hours before order is considered orphaned}
                            {--dry-run : Preview what would be cancelled without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancel WhatsApp orders not confirmed within timeout period and release stock';

    /**
     * Execute the console command.
     */
    public function handle(InventoryService $inventoryService): int
    {
        $hours = (int) $this->option('hours');
        $dryRun = $this->option('dry-run');
        $cutoff = Carbon::now()->subHours($hours);

        $this->info("🔍 Scanning for orphaned WhatsApp orders older than {$hours} hours...");
        $this->info("   Cutoff time: {$cutoff->toDateTimeString()}");
        
        if ($dryRun) {
            $this->warn("   [DRY RUN MODE - No changes will be made]");
        }

        // Find orphaned orders
        $orphanedOrders = Order::query()
            ->where('payment_method', PaymentLog::METHOD_WHATSAPP)
            ->where(function ($q) {
                $q->where('status', Order::STATUS_PENDING_PAYMENT)
                  ->orWhere('status', 'pending');
            })
            ->where('payment_status', Order::PAYMENT_PENDING)
            ->where('created_at', '<', $cutoff)
            ->with(['items.product', 'user'])
            ->get();

        if ($orphanedOrders->isEmpty()) {
            $this->info("✅ No orphaned orders found. System is clean.");
            return Command::SUCCESS;
        }

        $this->info("📋 Found {$orphanedOrders->count()} orphaned order(s):");
        $this->newLine();

        $table = [];
        foreach ($orphanedOrders as $order) {
            $table[] = [
                $order->order_number,
                $order->user?->name ?? 'Guest',
                'Rp ' . number_format($order->total_amount, 0, ',', '.'),
                $order->created_at->diffForHumans(),
                $order->items->count() . ' items',
            ];
        }

        $this->table(
            ['Order #', 'Customer', 'Total', 'Age', 'Items'],
            $table
        );

        if ($dryRun) {
            $this->warn("🛑 Dry run complete. Run without --dry-run to cancel these orders.");
            return Command::SUCCESS;
        }

        // Confirm before proceeding
        if (!$this->confirm("Cancel these {$orphanedOrders->count()} orders and release stock?")) {
            $this->info("Operation cancelled by user.");
            return Command::SUCCESS;
        }

        $cancelled = 0;
        $errors = 0;

        foreach ($orphanedOrders as $order) {
            $this->line("Processing order #{$order->order_number}...");

            try {
                DB::transaction(function () use ($order, $inventoryService, $hours) {
                    // Release stock from batch allocations
                    foreach ($order->items as $item) {
                        if (!empty($item->batch_allocations)) {
                            // Add product_id for legacy fallback
                            $allocations = collect($item->batch_allocations)->map(function ($alloc) use ($item) {
                                $alloc['product_id'] = $item->product_id;
                                return $alloc;
                            })->toArray();

                            $inventoryService->releaseStock($allocations, "Order #{$order->order_number} auto-cancelled after {$hours} hours");
                            
                            $this->info("   ↩ Released {$item->quantity}x {$item->product->name} back to inventory");
                        } else {
                            // Fallback: just increment global stock
                            $item->product->increment('stock', $item->quantity);
                            $this->warn("   ↩ Released {$item->quantity}x {$item->product->name} (no batch data - legacy mode)");
                        }
                    }

                    // Update order status
                    $order->update([
                        'status' => Order::STATUS_CANCELLED,
                        'notes' => $order->notes . "\n\n[AUTO-CANCELLED] " . now()->toDateTimeString() . "\nOrder automatically cancelled after {$hours} hours without payment confirmation.",
                    ]);

                    // Update payment log if exists
                    $order->paymentLogs()->update([
                        'status' => PaymentLog::STATUS_CANCELLED,
                        'metadata->cancelled_at' => now()->toIso8601String(),
                        'metadata->cancelled_reason' => 'timeout',
                    ]);

                    Log::info('Orphaned order cancelled', [
                        'order_number' => $order->order_number,
                        'order_id' => $order->id,
                        'user_id' => $order->user_id,
                        'total_amount' => $order->total_amount,
                        'items_count' => $order->items->count(),
                        'age_hours' => $order->created_at->diffInHours(now()),
                    ]);
                });

                $this->info("   ✅ Order #{$order->order_number} cancelled, stock released.");
                $cancelled++;

            } catch (\Exception $e) {
                $this->error("   ❌ Failed to cancel order #{$order->order_number}: {$e->getMessage()}");
                Log::error('Failed to cancel orphaned order', [
                    'order_number' => $order->order_number,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $errors++;
            }
        }

        $this->newLine();
        $this->info("📊 Cleanup Summary:");
        $this->info("   ✅ Cancelled: {$cancelled}");
        if ($errors > 0) {
            $this->error("   ❌ Errors: {$errors}");
        }
        $this->info("   📦 Stock released and available for new orders.");

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
