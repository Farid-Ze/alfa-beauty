<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('point_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('point_transactions', 'request_id')) {
                $table->uuid('request_id')->nullable()->after('order_id');
            }

            if (!Schema::hasColumn('point_transactions', 'idempotency_key')) {
                $table->string('idempotency_key', 191)->nullable()->after('request_id');
            }

            if (!Schema::hasColumn('point_transactions', 'balance_after')) {
                $table->integer('balance_after')->nullable()->after('description');
            }
        });

        Schema::table('point_transactions', function (Blueprint $table) {
            // Defense-in-depth: prevents double-award under retries/concurrency.
            // Multiple NULL idempotency_key values are allowed by SQL unique indexes.
            $table->unique(['idempotency_key'], 'uniq_point_transactions_idempotency_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('point_transactions', function (Blueprint $table) {
            $table->dropUnique('uniq_point_transactions_idempotency_key');
        });

        Schema::table('point_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('point_transactions', 'balance_after')) {
                $table->dropColumn('balance_after');
            }

            if (Schema::hasColumn('point_transactions', 'idempotency_key')) {
                $table->dropColumn('idempotency_key');
            }

            if (Schema::hasColumn('point_transactions', 'request_id')) {
                $table->dropColumn('request_id');
            }
        });
    }
};
