<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_returns', function (Blueprint $table) {
            if (!Schema::hasColumn('order_returns', 'inventory_restocked_at')) {
                $table->timestamp('inventory_restocked_at')->nullable()->after('completed_at');
                $table->index('inventory_restocked_at');
            }
        });

                // Defensive dedupe before enforcing uniqueness.
                // Keep the earliest row per (order_return_id, order_item_id).
                $driver = DB::getDriverName();
                if ($driver === 'sqlite') {
                        DB::statement(<<<SQL
DELETE FROM return_items
WHERE id NOT IN (
    SELECT MIN(id) FROM return_items GROUP BY order_return_id, order_item_id
)
SQL);
                } else {
                        DB::statement(<<<SQL
DELETE ri FROM return_items ri
INNER JOIN return_items dup
    ON dup.order_return_id = ri.order_return_id
 AND dup.order_item_id = ri.order_item_id
 AND dup.id < ri.id
SQL);
                }

        Schema::table('return_items', function (Blueprint $table) {
            $indexName = 'return_items_return_item_unique';
            $table->unique(['order_return_id', 'order_item_id'], $indexName);
        });
    }

    public function down(): void
    {
        Schema::table('return_items', function (Blueprint $table) {
            $table->dropUnique('return_items_return_item_unique');
        });

        Schema::table('order_returns', function (Blueprint $table) {
            if (Schema::hasColumn('order_returns', 'inventory_restocked_at')) {
                $table->dropIndex(['inventory_restocked_at']);
                $table->dropColumn('inventory_restocked_at');
            }
        });
    }
};
