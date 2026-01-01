-- ============================================================
-- ADD MISSING COLUMNS TO PRODUCTS TABLE
-- Run this in Supabase SQL Editor
-- ============================================================

-- Add missing B2B columns to products table
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS min_order_qty smallint DEFAULT 1,
ADD COLUMN IF NOT EXISTS order_increment smallint DEFAULT 1,
ADD COLUMN IF NOT EXISTS weight_grams integer DEFAULT 0,
ADD COLUMN IF NOT EXISTS length_mm integer,
ADD COLUMN IF NOT EXISTS width_mm integer,
ADD COLUMN IF NOT EXISTS height_mm integer,
ADD COLUMN IF NOT EXISTS selling_unit varchar DEFAULT 'pcs',
ADD COLUMN IF NOT EXISTS units_per_case smallint DEFAULT 12,
ADD COLUMN IF NOT EXISTS inci_list text,
ADD COLUMN IF NOT EXISTS how_to_use text,
ADD COLUMN IF NOT EXISTS is_halal boolean DEFAULT false,
ADD COLUMN IF NOT EXISTS is_vegan boolean DEFAULT false,
ADD COLUMN IF NOT EXISTS video_url varchar,
ADD COLUMN IF NOT EXISTS msds_url varchar;

-- Verify columns added
SELECT column_name, data_type, column_default 
FROM information_schema.columns 
WHERE table_name = 'products' 
ORDER BY ordinal_position;
