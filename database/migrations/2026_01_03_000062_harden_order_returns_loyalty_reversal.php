<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('order_returns')) {
            return;
        }

        Schema::table('order_returns', function (Blueprint $table) {
            if (!Schema::hasColumn('order_returns', 'loyalty_reversed_at')) {
                $table->timestamp('loyalty_reversed_at')->nullable()->after('inventory_restocked_at');
                $table->index('loyalty_reversed_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('order_returns')) {
            return;
        }

        Schema::table('order_returns', function (Blueprint $table) {
            if (Schema::hasColumn('order_returns', 'loyalty_reversed_at')) {
                $table->dropIndex(['loyalty_reversed_at']);
                $table->dropColumn('loyalty_reversed_at');
            }
        });
    }
};
