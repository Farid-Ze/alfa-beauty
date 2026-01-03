<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_logs', 'request_id')) {
                $table->uuid('request_id')->nullable()->after('order_id');
                $table->index('request_id');
            }

            if (!Schema::hasColumn('payment_logs', 'idempotency_key')) {
                $table->string('idempotency_key', 128)->nullable()->after('request_id');
                $table->index('idempotency_key');
            }
        });

        Schema::table('payment_logs', function (Blueprint $table) {
            $table->unique(['order_id', 'idempotency_key'], 'payment_logs_order_idempotency_unique');
            $table->unique(['order_id', 'reference_number'], 'payment_logs_order_reference_unique');
            $table->unique('external_id', 'payment_logs_external_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('payment_logs', function (Blueprint $table) {
            $table->dropUnique('payment_logs_order_idempotency_unique');
            $table->dropUnique('payment_logs_order_reference_unique');
            $table->dropUnique('payment_logs_external_id_unique');

            if (Schema::hasColumn('payment_logs', 'idempotency_key')) {
                $table->dropIndex(['idempotency_key']);
                $table->dropColumn('idempotency_key');
            }

            if (Schema::hasColumn('payment_logs', 'request_id')) {
                $table->dropIndex(['request_id']);
                $table->dropColumn('request_id');
            }
        });
    }
};
