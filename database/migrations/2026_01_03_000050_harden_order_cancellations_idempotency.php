<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('order_cancellations')) {
            return;
        }

        Schema::table('order_cancellations', function (Blueprint $table) {
            if (!Schema::hasColumn('order_cancellations', 'inventory_released_at')) {
                $table->timestamp('inventory_released_at')->nullable()->after('refund_completed_at');
            }
        });

        // Ensure one cancellation row per order (idempotency + integrity)
        // Defensive: deduplicate before adding unique constraint
        $duplicates = DB::table('order_cancellations')
            ->select('order_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('order_id')
            ->having('cnt', '>', 1)
            ->get();

        foreach ($duplicates as $dup) {
            $keepId = DB::table('order_cancellations')->where('order_id', $dup->order_id)->min('id');
            DB::table('order_cancellations')
                ->where('order_id', $dup->order_id)
                ->where('id', '!=', $keepId)
                ->delete();
        }

        Schema::table('order_cancellations', function (Blueprint $table) {
            // MySQL requires index name uniqueness; keep it explicit.
            $table->unique('order_id', 'order_cancellations_order_id_unique');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('order_cancellations')) {
            return;
        }

        Schema::table('order_cancellations', function (Blueprint $table) {
            $table->dropUnique('order_cancellations_order_id_unique');

            if (Schema::hasColumn('order_cancellations', 'inventory_released_at')) {
                $table->dropColumn('inventory_released_at');
            }
        });
    }
};
