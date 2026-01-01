-- Migration: Add Reviews Unique Constraint + Performance Indexes
-- From: 2026_01_01_200000 + 2026_01_01_300000
-- Date: 2026-01-02

-- ============================================================
-- 1. Add unique constraint to reviews (one review per user per product)
-- ============================================================

-- First drop the existing index if it exists
DROP INDEX IF EXISTS reviews_user_product_index;

-- Add unique constraint (prevents duplicate reviews from race conditions)
ALTER TABLE reviews 
    ADD CONSTRAINT reviews_user_product_unique UNIQUE (user_id, product_id);

-- ============================================================
-- 2. Additional Performance Indexes
-- ============================================================

-- Product name search optimization
CREATE INDEX IF NOT EXISTS idx_products_search_name ON products(is_active, name);

-- Order queries by user
CREATE INDEX IF NOT EXISTS idx_orders_user_created ON orders(user_id, created_at);
CREATE INDEX IF NOT EXISTS idx_orders_user_status ON orders(user_id, status);

-- Cart items cleanup queries  
CREATE INDEX IF NOT EXISTS idx_cart_items_created ON cart_items(created_at);

-- Point transactions by user
CREATE INDEX IF NOT EXISTS idx_point_transactions_user ON point_transactions(user_id, created_at);

-- ============================================================
-- 3. Update timestamps to TIMESTAMPTZ for reviews table
-- ============================================================

ALTER TABLE reviews 
    ALTER COLUMN approved_at TYPE TIMESTAMPTZ,
    ALTER COLUMN created_at TYPE TIMESTAMPTZ,
    ALTER COLUMN updated_at TYPE TIMESTAMPTZ;
