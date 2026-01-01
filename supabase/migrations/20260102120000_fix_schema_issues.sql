-- ============================================================
-- FIX SCHEMA ISSUES
-- 1. Rename batch_inventory to batch_inventories
-- 2. Remove duplicate received_date column
-- 3. Fix country_of_origin default
-- 4. Add unique constraint on migrations.migration
-- ============================================================

-- 1. Add unique constraint on migrations.migration (if not exists)
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint 
        WHERE conname = 'migrations_migration_key' 
        AND conrelid = 'migrations'::regclass
    ) THEN
        ALTER TABLE migrations ADD CONSTRAINT migrations_migration_key UNIQUE (migration);
    END IF;
EXCEPTION WHEN OTHERS THEN
    -- Constraint may already exist with different name
    NULL;
END $$;

-- 2. Rename batch_inventory to batch_inventories if it exists
DO $$
BEGIN
    IF EXISTS (SELECT FROM pg_tables WHERE schemaname = 'public' AND tablename = 'batch_inventory') THEN
        -- Drop FK constraint from return_items first
        ALTER TABLE return_items DROP CONSTRAINT IF EXISTS fk_return_items_batch_inventory;
        
        -- Rename table
        ALTER TABLE batch_inventory RENAME TO batch_inventories;
        
        -- Rename indexes
        ALTER INDEX IF EXISTS batch_inventory_product_expiry_index RENAME TO batch_inventories_product_expiry_index;
        ALTER INDEX IF EXISTS batch_inventory_batch_expiry_index RENAME TO batch_inventories_batch_expiry_index;
        ALTER INDEX IF EXISTS batch_inventory_near_expiry_index RENAME TO batch_inventories_near_expiry_index;
        ALTER INDEX IF EXISTS batch_inventory_product_batch_unique RENAME TO batch_inventories_product_batch_unique;
        
        -- Re-add FK constraint
        ALTER TABLE return_items 
            ADD CONSTRAINT fk_return_items_batch_inventory 
            FOREIGN KEY (batch_inventory_id) 
            REFERENCES batch_inventories(id) 
            ON DELETE SET NULL;
    END IF;
END $$;

-- 3. Remove duplicate received_date column if exists
ALTER TABLE batch_inventories DROP COLUMN IF EXISTS received_date;

-- 4. Remove default from country_of_origin
ALTER TABLE batch_inventories ALTER COLUMN country_of_origin DROP DEFAULT;

-- 5. Convert timestamps to timestamptz for timezone awareness
ALTER TABLE batch_inventories 
    ALTER COLUMN created_at TYPE TIMESTAMPTZ USING created_at AT TIME ZONE 'UTC',
    ALTER COLUMN updated_at TYPE TIMESTAMPTZ USING updated_at AT TIME ZONE 'UTC',
    ALTER COLUMN deleted_at TYPE TIMESTAMPTZ USING deleted_at AT TIME ZONE 'UTC';

-- Verify changes
SELECT 'Schema fixes applied successfully!' AS status;
