<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        // SQLite cannot add CHECK constraints reliably via ALTER TABLE.
        if ($driver === 'sqlite') {
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE user_loyalty_periods ADD CONSTRAINT chk_user_loyalty_periods_quarter_range CHECK ((period_quarter IS NULL) OR (period_quarter >= 1 AND period_quarter <= 4))");
            DB::statement("ALTER TABLE user_loyalty_periods ADD CONSTRAINT chk_user_loyalty_periods_period_dates CHECK (period_start <= period_end)");
            return;
        }

        if ($driver === 'mysql' || $driver === 'mariadb') {
            // MySQL 8.0.16+ enforces CHECK constraints; older versions parse but may ignore.
            DB::statement("ALTER TABLE user_loyalty_periods ADD CONSTRAINT chk_user_loyalty_periods_quarter_range CHECK ((period_quarter IS NULL) OR (period_quarter >= 1 AND period_quarter <= 4))");
            DB::statement("ALTER TABLE user_loyalty_periods ADD CONSTRAINT chk_user_loyalty_periods_period_dates CHECK (period_start <= period_end)");
            return;
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        foreach ([
            'chk_user_loyalty_periods_quarter_range',
            'chk_user_loyalty_periods_period_dates',
        ] as $constraint) {
            try {
                if ($driver === 'pgsql') {
                    DB::statement("ALTER TABLE user_loyalty_periods DROP CONSTRAINT IF EXISTS {$constraint}");
                } elseif ($driver === 'mysql' || $driver === 'mariadb') {
                    DB::statement("ALTER TABLE user_loyalty_periods DROP CHECK {$constraint}");
                }
            } catch (Throwable) {
                // Best-effort rollback; keep non-fatal.
            }
        }
    }
};
