-- Migration: Add missing columns to products table
-- These columns were defined in migration 2026_01_01_000001 but not applied to production
-- Date: 2026-01-02

-- ============================================================
-- 1. Add weight and dimension columns to products
-- ============================================================

ALTER TABLE products 
    ADD COLUMN IF NOT EXISTS weight_grams INTEGER DEFAULT 0,
    ADD COLUMN IF NOT EXISTS length_mm INTEGER NULL,
    ADD COLUMN IF NOT EXISTS width_mm INTEGER NULL,
    ADD COLUMN IF NOT EXISTS height_mm INTEGER NULL;

-- ============================================================
-- 2. Add Unit of Measure columns to products
-- ============================================================

ALTER TABLE products 
    ADD COLUMN IF NOT EXISTS selling_unit VARCHAR(20) DEFAULT 'pcs',
    ADD COLUMN IF NOT EXISTS units_per_case SMALLINT DEFAULT 12,
    ADD COLUMN IF NOT EXISTS min_order_qty SMALLINT DEFAULT 1,
    ADD COLUMN IF NOT EXISTS order_increment SMALLINT DEFAULT 1;

-- ============================================================
-- 3. Add missing columns to users table (B2B fields)
-- ============================================================

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS role VARCHAR(50) DEFAULT 'customer',
    ADD COLUMN IF NOT EXISTS customer_type VARCHAR(50) NULL,
    ADD COLUMN IF NOT EXISTS tier_evaluated_at TIMESTAMP NULL,
    ADD COLUMN IF NOT EXISTS tier_valid_until DATE NULL,
    ADD COLUMN IF NOT EXISTS current_period_spend NUMERIC(15,2) DEFAULT 0;

-- ============================================================
-- 4. Add missing columns to loyalty_tiers table
-- ============================================================

ALTER TABLE loyalty_tiers
    ADD COLUMN IF NOT EXISTS period_type VARCHAR(20) DEFAULT 'yearly',
    ADD COLUMN IF NOT EXISTS tier_validity_months SMALLINT DEFAULT 12,
    ADD COLUMN IF NOT EXISTS auto_downgrade BOOLEAN DEFAULT TRUE;

-- ============================================================
-- 5. Add missing tax columns to orders table
-- ============================================================

ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS subtotal_before_tax NUMERIC(15,2) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS tax_rate NUMERIC(5,2) DEFAULT 11.00,
    ADD COLUMN IF NOT EXISTS tax_amount NUMERIC(15,2) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS is_tax_inclusive BOOLEAN DEFAULT FALSE,
    ADD COLUMN IF NOT EXISTS e_faktur_number VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS e_faktur_date TIMESTAMP NULL,
    ADD COLUMN IF NOT EXISTS discount_breakdown JSONB NULL;

-- ============================================================
-- 6. Add missing payment tracking columns to orders table
-- ============================================================

ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS amount_paid NUMERIC(15,2) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS balance_due NUMERIC(15,2) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS payment_term_days SMALLINT DEFAULT 0,
    ADD COLUMN IF NOT EXISTS payment_due_date DATE NULL,
    ADD COLUMN IF NOT EXISTS last_payment_date TIMESTAMP NULL;

-- ============================================================
-- 7. Add missing tax columns to order_items table
-- ============================================================

ALTER TABLE order_items
    ADD COLUMN IF NOT EXISTS unit_price_before_tax NUMERIC(15,2) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS tax_rate NUMERIC(5,2) DEFAULT 11.00,
    ADD COLUMN IF NOT EXISTS tax_amount NUMERIC(15,2) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS subtotal_before_tax NUMERIC(15,2) DEFAULT 0;

-- ============================================================
-- 8. Add missing columns to customer_payment_terms table
-- ============================================================

ALTER TABLE customer_payment_terms
    ADD COLUMN IF NOT EXISTS term_type VARCHAR(20) DEFAULT 'cod',
    ADD COLUMN IF NOT EXISTS early_payment_discount_percent NUMERIC(5,2) NULL,
    ADD COLUMN IF NOT EXISTS early_payment_days INTEGER NULL,
    ADD COLUMN IF NOT EXISTS is_approved BOOLEAN DEFAULT FALSE,
    ADD COLUMN IF NOT EXISTS approved_by BIGINT NULL REFERENCES users(id),
    ADD COLUMN IF NOT EXISTS approved_at TIMESTAMP NULL,
    ADD COLUMN IF NOT EXISTS notes TEXT NULL;

-- ============================================================
-- 9. Add missing columns to customer_price_lists table
-- ============================================================

-- Make product_id nullable (can apply to brand/category level)
ALTER TABLE customer_price_lists
    ALTER COLUMN product_id DROP NOT NULL;

-- Make custom_price nullable (can use discount_percent instead)
ALTER TABLE customer_price_lists
    ALTER COLUMN custom_price DROP NOT NULL;

-- Add FK references if not exists
DO $$ 
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints 
                   WHERE constraint_name = 'customer_price_lists_brand_id_fkey') THEN
        ALTER TABLE customer_price_lists 
            ADD CONSTRAINT customer_price_lists_brand_id_fkey 
            FOREIGN KEY (brand_id) REFERENCES brands(id);
    END IF;
    
    IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints 
                   WHERE constraint_name = 'customer_price_lists_category_id_fkey') THEN
        ALTER TABLE customer_price_lists 
            ADD CONSTRAINT customer_price_lists_category_id_fkey 
            FOREIGN KEY (category_id) REFERENCES categories(id);
    END IF;
END $$;

-- ============================================================
-- 10. Add missing columns to payment_logs table
-- ============================================================

ALTER TABLE payment_logs
    ADD COLUMN IF NOT EXISTS confirmed_by BIGINT NULL REFERENCES users(id),
    ADD COLUMN IF NOT EXISTS confirmed_at TIMESTAMP NULL,
    ADD COLUMN IF NOT EXISTS external_id VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS metadata JSONB NULL,
    ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL;

-- ============================================================
-- 11. Create missing tables
-- ============================================================

-- Suppliers table
CREATE TABLE IF NOT EXISTS suppliers (
    id BIGSERIAL PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255) NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(20) NULL,
    address TEXT NULL,
    npwp VARCHAR(30) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    deleted_at TIMESTAMP NULL
);

-- Customer order settings table
CREATE TABLE IF NOT EXISTS customer_order_settings (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL UNIQUE REFERENCES users(id) ON DELETE CASCADE,
    min_order_amount NUMERIC(15,2) NULL,
    min_order_units INTEGER NULL,
    default_payment_term_days SMALLINT DEFAULT 0,
    credit_limit NUMERIC(15,2) NULL,
    current_credit_used NUMERIC(15,2) DEFAULT 0,
    free_shipping_eligible BOOLEAN DEFAULT FALSE,
    free_shipping_threshold NUMERIC(15,2) NULL,
    require_po_number BOOLEAN DEFAULT FALSE,
    allow_backorder BOOLEAN DEFAULT FALSE,
    allow_partial_delivery BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Order cancellations table
CREATE TABLE IF NOT EXISTS order_cancellations (
    id BIGSERIAL PRIMARY KEY,
    order_id BIGINT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    cancelled_by BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    reason_code VARCHAR(50) NOT NULL,
    reason_notes TEXT NULL,
    refund_amount NUMERIC(15,2) DEFAULT 0,
    refund_status VARCHAR(20) DEFAULT 'pending',
    refund_method VARCHAR(50) NULL,
    refund_completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Order returns table
CREATE TABLE IF NOT EXISTS order_returns (
    id BIGSERIAL PRIMARY KEY,
    return_number VARCHAR(50) UNIQUE NOT NULL,
    order_id BIGINT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    user_id BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    processed_by BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    status VARCHAR(20) DEFAULT 'requested',
    return_type VARCHAR(20) DEFAULT 'refund',
    reason_code VARCHAR(50) NOT NULL,
    reason_notes TEXT NULL,
    customer_notes TEXT NULL,
    return_value NUMERIC(15,2) DEFAULT 0,
    restocking_fee NUMERIC(15,2) DEFAULT 0,
    refund_amount NUMERIC(15,2) DEFAULT 0,
    refund_status VARCHAR(20) DEFAULT 'pending',
    approved_at TIMESTAMP NULL,
    received_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    deleted_at TIMESTAMP NULL
);

-- Return items table
CREATE TABLE IF NOT EXISTS return_items (
    id BIGSERIAL PRIMARY KEY,
    order_return_id BIGINT NOT NULL REFERENCES order_returns(id) ON DELETE CASCADE,
    order_item_id BIGINT NOT NULL REFERENCES order_items(id) ON DELETE CASCADE,
    product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    batch_inventory_id BIGINT NULL REFERENCES batch_inventories(id) ON DELETE SET NULL,
    quantity_requested INTEGER NOT NULL,
    quantity_received INTEGER DEFAULT 0,
    quantity_approved INTEGER DEFAULT 0,
    unit_price NUMERIC(15,2) NOT NULL,
    line_total NUMERIC(15,2) NOT NULL,
    condition VARCHAR(50) NULL,
    inspection_notes TEXT NULL,
    restock BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Discount rules table
CREATE TABLE IF NOT EXISTS discount_rules (
    id BIGSERIAL PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    discount_type VARCHAR(30) NOT NULL,
    discount_value NUMERIC(15,2) DEFAULT 0,
    buy_quantity INTEGER NULL,
    get_quantity INTEGER NULL,
    get_discount_percent NUMERIC(5,2) NULL,
    min_order_amount NUMERIC(15,2) NULL,
    min_quantity INTEGER NULL,
    max_discount_amount NUMERIC(15,2) NULL,
    usage_limit INTEGER NULL,
    usage_count INTEGER DEFAULT 0,
    per_user_limit INTEGER NULL,
    product_id BIGINT NULL REFERENCES products(id) ON DELETE SET NULL,
    brand_id BIGINT NULL REFERENCES brands(id) ON DELETE SET NULL,
    category_id BIGINT NULL REFERENCES categories(id) ON DELETE SET NULL,
    loyalty_tier_ids JSONB NULL,
    user_ids JSONB NULL,
    valid_from TIMESTAMP NULL,
    valid_until TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_stackable BOOLEAN DEFAULT FALSE,
    priority SMALLINT DEFAULT 100,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    deleted_at TIMESTAMP NULL
);

-- Order discounts table
CREATE TABLE IF NOT EXISTS order_discounts (
    id BIGSERIAL PRIMARY KEY,
    order_id BIGINT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    discount_rule_id BIGINT NULL REFERENCES discount_rules(id) ON DELETE SET NULL,
    order_item_id BIGINT NULL REFERENCES order_items(id) ON DELETE SET NULL,
    discount_type VARCHAR(30) NOT NULL,
    discount_code VARCHAR(50) NULL,
    discount_name VARCHAR(255) NOT NULL,
    original_amount NUMERIC(15,2) NOT NULL,
    discount_amount NUMERIC(15,2) NOT NULL,
    final_amount NUMERIC(15,2) NOT NULL,
    calculation_details JSONB NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Product MOQ overrides table
CREATE TABLE IF NOT EXISTS product_moq_overrides (
    id BIGSERIAL PRIMARY KEY,
    product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    user_id BIGINT NULL REFERENCES users(id) ON DELETE CASCADE,
    loyalty_tier_id BIGINT NULL REFERENCES loyalty_tiers(id) ON DELETE CASCADE,
    customer_type VARCHAR(50) NULL,
    min_order_qty SMALLINT NOT NULL,
    order_increment SMALLINT NULL,
    max_order_qty SMALLINT NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- User loyalty periods table
CREATE TABLE IF NOT EXISTS user_loyalty_periods (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    loyalty_tier_id BIGINT NOT NULL REFERENCES loyalty_tiers(id) ON DELETE CASCADE,
    period_year SMALLINT NOT NULL,
    period_quarter SMALLINT NULL,
    period_spend NUMERIC(15,2) DEFAULT 0,
    period_orders INTEGER DEFAULT 0,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    tier_qualified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Add supplier_id to batch_inventories if not exists
ALTER TABLE batch_inventories
    ADD COLUMN IF NOT EXISTS supplier_id BIGINT NULL REFERENCES suppliers(id) ON DELETE SET NULL,
    ADD COLUMN IF NOT EXISTS purchase_price NUMERIC(15,2) NULL,
    ADD COLUMN IF NOT EXISTS purchase_order_number VARCHAR(50) NULL;

-- ============================================================
-- 12. Create indexes for new tables
-- ============================================================

CREATE INDEX IF NOT EXISTS idx_order_cancellations_order ON order_cancellations(order_id);
CREATE INDEX IF NOT EXISTS idx_order_returns_order ON order_returns(order_id);
CREATE INDEX IF NOT EXISTS idx_order_returns_user ON order_returns(user_id);
CREATE INDEX IF NOT EXISTS idx_return_items_return ON return_items(order_return_id);
CREATE INDEX IF NOT EXISTS idx_discount_rules_code ON discount_rules(code);
CREATE INDEX IF NOT EXISTS idx_discount_rules_active ON discount_rules(is_active, valid_from, valid_until);
CREATE INDEX IF NOT EXISTS idx_order_discounts_order ON order_discounts(order_id);
CREATE INDEX IF NOT EXISTS idx_product_moq_product ON product_moq_overrides(product_id);
CREATE INDEX IF NOT EXISTS idx_user_loyalty_periods_user ON user_loyalty_periods(user_id);
CREATE INDEX IF NOT EXISTS idx_customer_order_settings_user ON customer_order_settings(user_id);
