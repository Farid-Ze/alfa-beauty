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
            DB::statement("ALTER TABLE reviews ADD CONSTRAINT chk_reviews_rating_range CHECK (rating >= 1 AND rating <= 5)");
            DB::statement("ALTER TABLE reviews ADD CONSTRAINT chk_reviews_points_awarded_requires_approved CHECK ((points_awarded = false) OR (is_approved = true))");
            DB::statement("ALTER TABLE reviews ADD CONSTRAINT chk_reviews_approved_requires_timestamp CHECK ((is_approved = false) OR (approved_at IS NOT NULL))");
            return;
        }

        if ($driver === 'mysql' || $driver === 'mariadb') {
            // MySQL 8.0.16+ enforces CHECK constraints; older versions parse but may ignore.
            DB::statement("ALTER TABLE reviews ADD CONSTRAINT chk_reviews_rating_range CHECK (rating >= 1 AND rating <= 5)");
            DB::statement("ALTER TABLE reviews ADD CONSTRAINT chk_reviews_points_awarded_requires_approved CHECK ((points_awarded = 0) OR (is_approved = 1))");
            DB::statement("ALTER TABLE reviews ADD CONSTRAINT chk_reviews_approved_requires_timestamp CHECK ((is_approved = 0) OR (approved_at IS NOT NULL))");
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
            'chk_reviews_rating_range',
            'chk_reviews_points_awarded_requires_approved',
            'chk_reviews_approved_requires_timestamp',
        ] as $constraint) {
            try {
                if ($driver === 'pgsql') {
                    DB::statement("ALTER TABLE reviews DROP CONSTRAINT IF EXISTS {$constraint}");
                } elseif ($driver === 'mysql' || $driver === 'mariadb') {
                    DB::statement("ALTER TABLE reviews DROP CHECK {$constraint}");
                }
            } catch (Throwable) {
                // Best-effort rollback; keep non-fatal.
            }
        }
    }
};
