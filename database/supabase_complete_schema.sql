-- ============================================================
-- ALFA BEAUTY - Complete Database Schema for Supabase
-- Run this script in Supabase SQL Editor to add missing columns
-- ============================================================

-- ============================================
-- 1. USERS TABLE UPDATES
-- ============================================

-- Add missing columns for B2B and loyalty features
ALTER TABLE users ADD COLUMN IF NOT EXISTS company_name VARCHAR(255);
ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(255);
ALTER TABLE users ADD COLUMN IF NOT EXISTS loyalty_tier_id BIGINT;
ALTER TABLE users ADD COLUMN IF NOT EXISTS points INTEGER DEFAULT 0;
ALTER TABLE users ADD COLUMN IF NOT EXISTS total_spend DECIMAL(15,2) DEFAULT 0;

-- Add foreign key constraint if loyalty_tiers table exists
-- ALTER TABLE users ADD CONSTRAINT users_loyalty_tier_id_fkey 
--     FOREIGN KEY (loyalty_tier_id) REFERENCES loyalty_tiers(id) ON DELETE SET NULL;

-- ============================================
-- 2. ORDERS TABLE UPDATES
-- ============================================

-- Add missing columns for order tracking
ALTER TABLE orders ADD COLUMN IF NOT EXISTS discount_percent DECIMAL(5,2) DEFAULT 0;

-- ============================================
-- 3. CUSTOMER_PRICE_LISTS TABLE UPDATES (B2B Pricing)
-- ============================================

-- Add columns for flexible B2B pricing (by brand, category, or global)
ALTER TABLE customer_price_lists ADD COLUMN IF NOT EXISTS brand_id BIGINT;
ALTER TABLE customer_price_lists ADD COLUMN IF NOT EXISTS category_id BIGINT;
ALTER TABLE customer_price_lists ADD COLUMN IF NOT EXISTS discount_percent DECIMAL(5,2);
ALTER TABLE customer_price_lists ADD COLUMN IF NOT EXISTS min_quantity INTEGER DEFAULT 1;
ALTER TABLE customer_price_lists ADD COLUMN IF NOT EXISTS priority INTEGER DEFAULT 0;

-- Add foreign key constraints
-- ALTER TABLE customer_price_lists ADD CONSTRAINT customer_price_lists_brand_id_fkey 
--     FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE;
-- ALTER TABLE customer_price_lists ADD CONSTRAINT customer_price_lists_category_id_fkey 
--     FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE;

-- ============================================
-- 4. BATCH_INVENTORIES TABLE UPDATES (BPOM Compliance)
-- ============================================

-- Add columns for full FEFO/BPOM tracking
ALTER TABLE batch_inventories ADD COLUMN IF NOT EXISTS lot_number VARCHAR(255);
ALTER TABLE batch_inventories ADD COLUMN IF NOT EXISTS quantity_sold INTEGER DEFAULT 0;
ALTER TABLE batch_inventories ADD COLUMN IF NOT EXISTS quantity_damaged INTEGER DEFAULT 0;
ALTER TABLE batch_inventories ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT true;
ALTER TABLE batch_inventories ADD COLUMN IF NOT EXISTS is_expired BOOLEAN DEFAULT false;
ALTER TABLE batch_inventories ADD COLUMN IF NOT EXISTS near_expiry_discount_percent DECIMAL(5,2);
ALTER TABLE batch_inventories ADD COLUMN IF NOT EXISTS warehouse_id BIGINT;
ALTER TABLE batch_inventories ADD COLUMN IF NOT EXISTS supplier_name VARCHAR(255);
ALTER TABLE batch_inventories ADD COLUMN IF NOT EXISTS country_of_origin VARCHAR(255);
ALTER TABLE batch_inventories ADD COLUMN IF NOT EXISTS metadata JSONB;
ALTER TABLE batch_inventories ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP;

-- ============================================
-- 5. PAYMENT_LOGS TABLE UPDATES
-- ============================================

-- Add missing columns for payment tracking
ALTER TABLE payment_logs ADD COLUMN IF NOT EXISTS payment_method VARCHAR(255);
ALTER TABLE payment_logs ADD COLUMN IF NOT EXISTS provider VARCHAR(255);
ALTER TABLE payment_logs ADD COLUMN IF NOT EXISTS currency VARCHAR(10) DEFAULT 'IDR';
ALTER TABLE payment_logs ADD COLUMN IF NOT EXISTS confirmed_by BIGINT;
ALTER TABLE payment_logs ADD COLUMN IF NOT EXISTS confirmed_at TIMESTAMP;
ALTER TABLE payment_logs ADD COLUMN IF NOT EXISTS external_id VARCHAR(255);
ALTER TABLE payment_logs ADD COLUMN IF NOT EXISTS metadata JSONB;
ALTER TABLE payment_logs ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP;

-- If you prefer using 'method' column name (existing), copy data:
-- UPDATE payment_logs SET payment_method = method WHERE payment_method IS NULL;

-- ============================================
-- 6. PRODUCT_PRICE_TIERS TABLE UPDATES (Volume Pricing)
-- ============================================

-- Ensure all columns exist for volume-based pricing
ALTER TABLE product_price_tiers ADD COLUMN IF NOT EXISTS max_quantity INTEGER;
ALTER TABLE product_price_tiers ADD COLUMN IF NOT EXISTS unit_price DECIMAL(15,2);
ALTER TABLE product_price_tiers ADD COLUMN IF NOT EXISTS discount_percent DECIMAL(5,2);

-- ============================================
-- 7. LOYALTY_TIERS TABLE (if not exists)
-- ============================================

-- Create if not exists
CREATE TABLE IF NOT EXISTS loyalty_tiers (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    min_spend DECIMAL(15,2) DEFAULT 0,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    point_multiplier DECIMAL(5,2) DEFAULT 1,
    free_shipping BOOLEAN DEFAULT false,
    badge_color VARCHAR(50),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Insert default tiers if empty
INSERT INTO loyalty_tiers (name, slug, min_spend, discount_percent, point_multiplier, badge_color)
SELECT 'Guest', 'guest', 0, 0, 1, '#808080'
WHERE NOT EXISTS (SELECT 1 FROM loyalty_tiers WHERE slug = 'guest');

INSERT INTO loyalty_tiers (name, slug, min_spend, discount_percent, point_multiplier, badge_color)
SELECT 'Bronze', 'bronze', 0, 0, 1, '#CD7F32'
WHERE NOT EXISTS (SELECT 1 FROM loyalty_tiers WHERE slug = 'bronze');

INSERT INTO loyalty_tiers (name, slug, min_spend, discount_percent, point_multiplier, badge_color)
SELECT 'Silver', 'silver', 5000000, 5, 1.25, '#C0C0C0'
WHERE NOT EXISTS (SELECT 1 FROM loyalty_tiers WHERE slug = 'silver');

INSERT INTO loyalty_tiers (name, slug, min_spend, discount_percent, point_multiplier, badge_color)
SELECT 'Gold', 'gold', 15000000, 10, 1.5, '#FFD700'
WHERE NOT EXISTS (SELECT 1 FROM loyalty_tiers WHERE slug = 'gold');

-- ============================================
-- 8. POINT_TRANSACTIONS TABLE UPDATES
-- ============================================

-- Rename column if needed (points vs amount)
-- ALTER TABLE point_transactions RENAME COLUMN points TO amount;
-- Or add if missing:
ALTER TABLE point_transactions ADD COLUMN IF NOT EXISTS amount INTEGER;
ALTER TABLE point_transactions ADD COLUMN IF NOT EXISTS balance_after INTEGER;
ALTER TABLE point_transactions ADD COLUMN IF NOT EXISTS type VARCHAR(50);
ALTER TABLE point_transactions ADD COLUMN IF NOT EXISTS description TEXT;

-- If you have 'points' column but code expects 'amount':
-- UPDATE point_transactions SET amount = points WHERE amount IS NULL;

-- ============================================
-- VERIFICATION QUERIES
-- ============================================

-- Run these to verify columns were added:
-- SELECT column_name FROM information_schema.columns WHERE table_name = 'users';
-- SELECT column_name FROM information_schema.columns WHERE table_name = 'customer_price_lists';
-- SELECT column_name FROM information_schema.columns WHERE table_name = 'batch_inventories';
-- SELECT column_name FROM information_schema.columns WHERE table_name = 'payment_logs';
-- SELECT column_name FROM information_schema.columns WHERE table_name = 'orders';

-- ============================================
-- NOTES
-- ============================================
-- 
-- After running this script:
-- 1. Run the verification queries above to confirm columns exist
-- 2. The application code may need to be reverted to original versions
-- 3. Test each feature: Register, Cart, Checkout, B2B Pricing
--
-- If any ALTER TABLE fails with "column already exists", that's OK - 
-- Supabase's IF NOT EXISTS should handle it, but older PostgreSQL 
-- versions may not support it. In that case, skip that line.
