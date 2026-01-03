<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add external ID mapping columns for ERP/WMS integration.
 * 
 * GOVERNANCE:
 * - external_order_id: External system reference (Accurate, Jurnal, etc)
 * - external_system: Which system the ID belongs to
 * - synced_at: Timestamp of last successful sync
 * 
 * This enables traceability across system boundaries.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'external_order_id')) {
                $table->string('external_order_id', 64)->nullable()->after('idempotency_key');
                $table->index('external_order_id', 'orders_external_order_id_index');
            }
            
            if (!Schema::hasColumn('orders', 'external_system')) {
                $table->string('external_system', 32)->nullable()->after('external_order_id');
            }
            
            if (!Schema::hasColumn('orders', 'synced_at')) {
                $table->timestamp('synced_at')->nullable()->after('external_system');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'external_order_id')) {
                $table->dropIndex('orders_external_order_id_index');
                $table->dropColumn('external_order_id');
            }
            if (Schema::hasColumn('orders', 'external_system')) {
                $table->dropColumn('external_system');
            }
            if (Schema::hasColumn('orders', 'synced_at')) {
                $table->dropColumn('synced_at');
            }
        });
    }
};
