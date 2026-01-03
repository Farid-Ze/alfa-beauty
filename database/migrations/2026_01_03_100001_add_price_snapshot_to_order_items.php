<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add price snapshot isolation columns to order_items.
 * 
 * GOVERNANCE:
 * - price_locked_at: Timestamp when price was locked (immutable after set)
 * - pricing_metadata: Full pricing context at time of order (for audit)
 * 
 * This ensures price integrity: once locked, prices cannot be recalculated.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'price_locked_at')) {
                $table->timestamp('price_locked_at')->nullable()->after('batch_allocations');
            }
            
            if (!Schema::hasColumn('order_items', 'pricing_metadata')) {
                $table->jsonb('pricing_metadata')->nullable()->after('price_locked_at');
            }
        });
        
        // Backfill existing order_items with price_locked_at = created_at
        // This ensures historical data has immutable pricing
        \Illuminate\Support\Facades\DB::statement("
            UPDATE order_items 
            SET price_locked_at = created_at 
            WHERE price_locked_at IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'price_locked_at')) {
                $table->dropColumn('price_locked_at');
            }
            if (Schema::hasColumn('order_items', 'pricing_metadata')) {
                $table->dropColumn('pricing_metadata');
            }
        });
    }
};
