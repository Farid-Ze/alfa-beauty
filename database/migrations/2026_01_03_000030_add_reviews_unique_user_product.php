<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Best-effort de-duplication before adding unique constraint.
        // Keeps the lowest id per (user_id, product_id) and deletes the rest.
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement(<<<'SQL'
                DELETE FROM reviews r
                USING (
                    SELECT MIN(id) AS keep_id, user_id, product_id
                    FROM reviews
                    GROUP BY user_id, product_id
                    HAVING COUNT(*) > 1
                ) d
                WHERE r.user_id = d.user_id
                  AND r.product_id = d.product_id
                  AND r.id <> d.keep_id
            SQL);
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement(<<<'SQL'
                DELETE r
                FROM reviews r
                JOIN (
                    SELECT MIN(id) AS keep_id, user_id, product_id
                    FROM reviews
                    GROUP BY user_id, product_id
                    HAVING COUNT(*) > 1
                ) d
                  ON r.user_id = d.user_id
                 AND r.product_id = d.product_id
                 AND r.id <> d.keep_id
            SQL);
        }

        Schema::table('reviews', function (Blueprint $table) {
            $table->unique(['user_id', 'product_id'], 'uniq_reviews_user_product');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique('uniq_reviews_user_product');
        });
    }
};
