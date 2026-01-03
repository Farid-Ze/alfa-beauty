<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('audit_events')) {
            return;
        }

        // Defensive dedupe: keep the earliest row per idempotency_key.
                $driver = DB::getDriverName();
                if ($driver === 'sqlite') {
                        DB::statement(<<<SQL
DELETE FROM audit_events
WHERE idempotency_key IS NOT NULL
    AND id NOT IN (
        SELECT MIN(id) FROM audit_events WHERE idempotency_key IS NOT NULL GROUP BY idempotency_key
    )
SQL);
                } else {
                        DB::statement(<<<SQL
DELETE ae FROM audit_events ae
INNER JOIN audit_events dup
    ON dup.idempotency_key = ae.idempotency_key
 AND dup.id < ae.id
WHERE ae.idempotency_key IS NOT NULL
SQL);
                }

        Schema::table('audit_events', function (Blueprint $table) {
            // Unique idempotency keys prevent duplicate governance events on retries.
            $table->unique('idempotency_key', 'audit_events_idempotency_key_unique');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('audit_events')) {
            return;
        }

        Schema::table('audit_events', function (Blueprint $table) {
            $table->dropUnique('audit_events_idempotency_key_unique');
        });
    }
};
