<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Add Check Constraint to CustomerPriceList
 * 
 * Solves: All three FK columns (product_id, brand_id, category_id) can be NULL
 * 
 * Design Decisions:
 * - At least one must be set for the price list to have a target
 * - Uses raw SQL for check constraint (not supported in Blueprint)
 * - SQLite handles this via trigger, PostgreSQL/MySQL via CHECK
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'pgsql') {
            // PostgreSQL - native CHECK constraint
            DB::statement('
                ALTER TABLE customer_price_lists 
                ADD CONSTRAINT customer_price_lists_target_check 
                CHECK (
                    product_id IS NOT NULL OR 
                    brand_id IS NOT NULL OR 
                    category_id IS NOT NULL
                )
            ');
        } elseif ($driver === 'mysql') {
            // MySQL 8.0.16+ supports CHECK constraints
            DB::statement('
                ALTER TABLE customer_price_lists 
                ADD CONSTRAINT customer_price_lists_target_check 
                CHECK (
                    product_id IS NOT NULL OR 
                    brand_id IS NOT NULL OR 
                    category_id IS NOT NULL
                )
            ');
        } elseif ($driver === 'sqlite') {
            // SQLite - use trigger for validation
            DB::statement('
                CREATE TRIGGER customer_price_lists_target_check_insert
                BEFORE INSERT ON customer_price_lists
                BEGIN
                    SELECT CASE 
                        WHEN NEW.product_id IS NULL 
                         AND NEW.brand_id IS NULL 
                         AND NEW.category_id IS NULL 
                        THEN RAISE(ABORT, "At least one of product_id, brand_id, or category_id must be set")
                    END;
                END
            ');
            
            DB::statement('
                CREATE TRIGGER customer_price_lists_target_check_update
                BEFORE UPDATE ON customer_price_lists
                BEGIN
                    SELECT CASE 
                        WHEN NEW.product_id IS NULL 
                         AND NEW.brand_id IS NULL 
                         AND NEW.category_id IS NULL 
                        THEN RAISE(ABORT, "At least one of product_id, brand_id, or category_id must be set")
                    END;
                END
            ');
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'pgsql' || $driver === 'mysql') {
            DB::statement('ALTER TABLE customer_price_lists DROP CONSTRAINT customer_price_lists_target_check');
        } elseif ($driver === 'sqlite') {
            DB::statement('DROP TRIGGER IF EXISTS customer_price_lists_target_check_insert');
            DB::statement('DROP TRIGGER IF EXISTS customer_price_lists_target_check_update');
        }
    }
};
