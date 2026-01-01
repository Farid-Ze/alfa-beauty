-- ============================================================
-- ALFA BEAUTY - Complete PostgreSQL Database Schema
-- Compatible with Supabase, Neon, or any PostgreSQL
-- 
-- Instructions:
-- 1. Copy this entire file
-- 2. Paste into Supabase SQL Editor or Neon Console
-- 3. Click "Run" to execute
-- 
-- Total: 33 Migrations consolidated
-- Generated: 2026-01-01
-- Updated: 2026-01-02 (Added DROP statements for clean reset)
-- ============================================================

-- ============================================================
-- SECTION 1: MIGRATIONS TRACKING
-- ============================================================

CREATE TABLE IF NOT EXISTS migrations (
    id SERIAL PRIMARY KEY,
    migration VARCHAR(255) UNIQUE NOT NULL,
    batch INTEGER NOT NULL
);

-- ============================================================
-- SECTION 2: LOYALTY TIERS (must be first for FK references)
-- ============================================================

CREATE TABLE IF NOT EXISTS loyalty_tiers (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    min_spend DECIMAL(15,2) DEFAULT 0,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    point_multiplier DECIMAL(5,2) DEFAULT 1,
    free_shipping BOOLEAN DEFAULT FALSE,
    badge_color VARCHAR(50) NULL,
    period_type VARCHAR(20) DEFAULT 'yearly',
    tier_validity_months SMALLINT DEFAULT 12,
    auto_downgrade BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- ============================================================
-- SECTION 3: SUPPLIERS (for batch inventory FK)
-- ============================================================

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

-- ============================================================
-- SECTION 4: USERS TABLE (with all B2B fields)
-- ============================================================

CREATE TABLE IF NOT EXISTS users (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL,
    role VARCHAR(30) DEFAULT 'customer',
    -- B2B Fields
    phone VARCHAR(255) NULL,
    company_name VARCHAR(255) NULL,
    business_name VARCHAR(255) NULL,
    business_type VARCHAR(255) NULL,
    customer_type VARCHAR(30) NULL,
    npwp VARCHAR(30) NULL,
    address TEXT NULL,
    city VARCHAR(255) NULL,
    -- Loyalty Fields
    loyalty_tier_id BIGINT NULL REFERENCES loyalty_tiers(id) ON DELETE SET NULL,
    tier_evaluated_at TIMESTAMP NULL,
    tier_valid_until DATE NULL,
    current_period_spend DECIMAL(15,2) DEFAULT 0,
    total_points INTEGER DEFAULT 0,
    points INTEGER DEFAULT 0,
    annual_spend DECIMAL(15,2) DEFAULT 0,
    total_spend DECIMAL(15,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS users_loyalty_tier_index ON users(loyalty_tier_id);
CREATE INDEX IF NOT EXISTS users_customer_type_index ON users(customer_type);

-- Password reset tokens
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    email VARCHAR(255) PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL
);

-- Sessions table
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    payload TEXT NOT NULL,
    last_activity INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS sessions_user_id_index ON sessions(user_id);
CREATE INDEX IF NOT EXISTS sessions_last_activity_index ON sessions(last_activity);

-- ============================================================
-- SECTION 5: CACHE, JOBS, NOTIFICATIONS
-- ============================================================

-- Cache table
CREATE TABLE IF NOT EXISTS cache (
    key VARCHAR(255) PRIMARY KEY,
    value TEXT NOT NULL,
    expiration INTEGER NOT NULL
);

-- Cache locks
CREATE TABLE IF NOT EXISTS cache_locks (
    key VARCHAR(255) PRIMARY KEY,
    owner VARCHAR(255) NOT NULL,
    expiration INTEGER NOT NULL
);

-- Jobs table
CREATE TABLE IF NOT EXISTS jobs (
    id BIGSERIAL PRIMARY KEY,
    queue VARCHAR(255) NOT NULL,
    payload TEXT NOT NULL,
    attempts SMALLINT NOT NULL,
    reserved_at INTEGER NULL,
    available_at INTEGER NOT NULL,
    created_at INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS jobs_queue_index ON jobs(queue);

-- Job batches
CREATE TABLE IF NOT EXISTS job_batches (
    id VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    total_jobs INTEGER NOT NULL,
    pending_jobs INTEGER NOT NULL,
    failed_jobs INTEGER NOT NULL,
    failed_job_ids TEXT NOT NULL,
    options TEXT NULL,
    cancelled_at INTEGER NULL,
    created_at INTEGER NOT NULL,
    finished_at INTEGER NULL
);

-- Failed jobs
CREATE TABLE IF NOT EXISTS failed_jobs (
    id BIGSERIAL PRIMARY KEY,
    uuid VARCHAR(255) UNIQUE NOT NULL,
    connection TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload TEXT NOT NULL,
    exception TEXT NOT NULL,
    failed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL
);

-- Notifications
CREATE TABLE IF NOT EXISTS notifications (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    type VARCHAR(255) NOT NULL,
    notifiable_type VARCHAR(255) NOT NULL,
    notifiable_id BIGINT NOT NULL,
    data TEXT NOT NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS notifications_notifiable_index ON notifications(notifiable_type, notifiable_id);

-- ============================================================
-- SECTION 6: BRANDS & CATEGORIES
-- ============================================================

-- Brands
CREATE TABLE IF NOT EXISTS brands (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    logo_url VARCHAR(255) NULL,
    description TEXT NULL,
    origin_country VARCHAR(255) NULL,
    is_own_brand BOOLEAN DEFAULT FALSE,
    is_featured BOOLEAN DEFAULT FALSE,
    sort_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Categories
CREATE TABLE IF NOT EXISTS categories (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    parent_id BIGINT NULL REFERENCES categories(id) ON DELETE SET NULL,
    description TEXT NULL,
    icon VARCHAR(255) NULL,
    sort_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- ============================================================
-- SECTION 7: PRODUCTS (with weight, UoM, MOQ fields)
-- ============================================================

CREATE TABLE IF NOT EXISTS products (
    id BIGSERIAL PRIMARY KEY,
    sku VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    brand_id BIGINT NOT NULL REFERENCES brands(id) ON DELETE CASCADE,
    category_id BIGINT NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
    base_price DECIMAL(15,2) NOT NULL,
    stock INTEGER DEFAULT 0,
    -- Weight & Dimensions (from migration 2026_01_01_000001)
    weight_grams INTEGER DEFAULT 0,
    length_mm INTEGER NULL,
    width_mm INTEGER NULL,
    height_mm INTEGER NULL,
    -- Unit of Measure
    selling_unit VARCHAR(20) DEFAULT 'pcs',
    units_per_case SMALLINT DEFAULT 12,
    min_order_qty SMALLINT DEFAULT 1,
    order_increment SMALLINT DEFAULT 1,
    -- Product Info
    description TEXT NULL,
    inci_list TEXT NULL,
    how_to_use TEXT NULL,
    is_halal BOOLEAN DEFAULT FALSE,
    is_vegan BOOLEAN DEFAULT FALSE,
    bpom_number VARCHAR(255) NULL,
    images JSONB NULL,
    video_url VARCHAR(255) NULL,
    msds_url VARCHAR(255) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Product Indexes (from migration 2026_01_01_000010)
CREATE INDEX IF NOT EXISTS products_sku_index ON products(sku);
CREATE INDEX IF NOT EXISTS products_brand_active_index ON products(brand_id, is_active);
CREATE INDEX IF NOT EXISTS products_category_active_index ON products(category_id, is_active);
CREATE INDEX IF NOT EXISTS idx_products_active_brand ON products(is_active, brand_id);
CREATE INDEX IF NOT EXISTS idx_products_active_category ON products(is_active, category_id);
CREATE INDEX IF NOT EXISTS idx_products_active_stock ON products(is_active, stock);
CREATE INDEX IF NOT EXISTS idx_products_active_featured ON products(is_active, is_featured);
CREATE INDEX IF NOT EXISTS idx_products_active_price ON products(is_active, base_price);
CREATE INDEX IF NOT EXISTS idx_products_moq_active ON products(min_order_qty, is_active);

-- ============================================================
-- SECTION 8: PRODUCT PRICE TIERS (Volume Pricing)
-- ============================================================

CREATE TABLE IF NOT EXISTS product_price_tiers (
    id BIGSERIAL PRIMARY KEY,
    product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    min_quantity INTEGER NOT NULL,
    max_quantity INTEGER NULL,
    price DECIMAL(15,2) NULL,
    unit_price DECIMAL(15,2) NULL,
    discount_percent DECIMAL(5,2) NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS product_price_tiers_product_index ON product_price_tiers(product_id);
CREATE INDEX IF NOT EXISTS product_price_tiers_lookup_index ON product_price_tiers(product_id, min_quantity);

-- ============================================================
-- SECTION 9: ORDERS (with tax, payment tracking)
-- ============================================================

CREATE TABLE IF NOT EXISTS orders (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    order_number VARCHAR(255) UNIQUE NOT NULL,
    status VARCHAR(255) DEFAULT 'pending',
    payment_status VARCHAR(255) DEFAULT 'unpaid',
    payment_method VARCHAR(255) NULL,
    -- Amounts
    subtotal DECIMAL(15,2) DEFAULT 0,
    subtotal_before_tax DECIMAL(15,2) DEFAULT 0,
    total_amount DECIMAL(15,2) NOT NULL,
    discount_amount DECIMAL(15,2) DEFAULT 0,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    discount_source VARCHAR(255) NULL,
    discount_breakdown JSONB NULL,
    tier_discount_percent DECIMAL(5,2) DEFAULT 0,
    -- Tax (from migration 2026_01_01_000002)
    tax_rate DECIMAL(5,2) DEFAULT 11.00,
    tax_amount DECIMAL(15,2) DEFAULT 0,
    is_tax_inclusive BOOLEAN DEFAULT FALSE,
    e_faktur_number VARCHAR(50) NULL,
    e_faktur_date TIMESTAMP NULL,
    -- Payment Tracking (from migration 2026_01_01_000003)
    amount_paid DECIMAL(15,2) DEFAULT 0,
    balance_due DECIMAL(15,2) DEFAULT 0,
    payment_term_days SMALLINT DEFAULT 0,
    payment_due_date DATE NULL,
    last_payment_date TIMESTAMP NULL,
    -- Shipping
    shipping_address TEXT NULL,
    shipping_method VARCHAR(255) NULL,
    shipping_cost DECIMAL(15,2) DEFAULT 0,
    -- Customer Info
    customer_name VARCHAR(255) NULL,
    customer_phone VARCHAR(255) NULL,
    -- Loyalty
    points_earned INTEGER DEFAULT 0,
    -- Notes
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS orders_user_index ON orders(user_id);
CREATE INDEX IF NOT EXISTS orders_status_index ON orders(status);
CREATE INDEX IF NOT EXISTS orders_number_index ON orders(order_number);
CREATE INDEX IF NOT EXISTS orders_efaktur_index ON orders(e_faktur_number);
CREATE INDEX IF NOT EXISTS orders_payment_status_due_index ON orders(payment_status, payment_due_date);
CREATE INDEX IF NOT EXISTS orders_balance_due_index ON orders(balance_due);

-- ============================================================
-- SECTION 10: ORDER ITEMS (with tax fields)
-- ============================================================

CREATE TABLE IF NOT EXISTS order_items (
    id BIGSERIAL PRIMARY KEY,
    order_id BIGINT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    quantity INTEGER NOT NULL,
    unit_price DECIMAL(15,2) NOT NULL,
    unit_price_before_tax DECIMAL(15,2) DEFAULT 0,
    tax_rate DECIMAL(5,2) DEFAULT 11.00,
    tax_amount DECIMAL(15,2) DEFAULT 0,
    subtotal_before_tax DECIMAL(15,2) DEFAULT 0,
    total_price DECIMAL(15,2) NOT NULL,
    batch_allocations JSONB NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS order_items_order_index ON order_items(order_id);
CREATE INDEX IF NOT EXISTS order_items_product_index ON order_items(product_id);
CREATE INDEX IF NOT EXISTS idx_order_items_order_product ON order_items(order_id, product_id);

-- ============================================================
-- SECTION 11: CARTS & CART ITEMS
-- ============================================================

CREATE TABLE IF NOT EXISTS carts (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NULL REFERENCES users(id) ON DELETE CASCADE,
    session_id VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS carts_user_index ON carts(user_id);
CREATE INDEX IF NOT EXISTS carts_session_index ON carts(session_id);

CREATE TABLE IF NOT EXISTS cart_items (
    id BIGSERIAL PRIMARY KEY,
    cart_id BIGINT NOT NULL REFERENCES carts(id) ON DELETE CASCADE,
    product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    quantity INTEGER DEFAULT 1,
    price_at_add DECIMAL(15,2) NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS cart_items_cart_index ON cart_items(cart_id);
CREATE INDEX IF NOT EXISTS cart_items_product_index ON cart_items(product_id);
CREATE INDEX IF NOT EXISTS idx_cart_items_cart_product ON cart_items(cart_id, product_id);

-- ============================================================
-- SECTION 12: ORDER CANCELLATIONS & RETURNS
-- ============================================================

-- Order Cancellations
CREATE TABLE IF NOT EXISTS order_cancellations (
    id BIGSERIAL PRIMARY KEY,
    order_id BIGINT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    cancelled_by BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    reason_code VARCHAR(50) NOT NULL,
    reason_notes TEXT NULL,
    refund_amount DECIMAL(15,2) DEFAULT 0,
    refund_status VARCHAR(30) DEFAULT 'pending',
    refund_method VARCHAR(30) NULL,
    refund_completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS order_cancellations_order_status_index ON order_cancellations(order_id, refund_status);
CREATE INDEX IF NOT EXISTS order_cancellations_reason_index ON order_cancellations(reason_code);

-- Order Returns
CREATE TABLE IF NOT EXISTS order_returns (
    id BIGSERIAL PRIMARY KEY,
    return_number VARCHAR(30) UNIQUE NOT NULL,
    order_id BIGINT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    user_id BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    processed_by BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    status VARCHAR(30) DEFAULT 'requested',
    return_type VARCHAR(30) DEFAULT 'refund',
    reason_code VARCHAR(50) NOT NULL,
    reason_notes TEXT NULL,
    customer_notes TEXT NULL,
    return_value DECIMAL(15,2) DEFAULT 0,
    restocking_fee DECIMAL(15,2) DEFAULT 0,
    refund_amount DECIMAL(15,2) DEFAULT 0,
    refund_status VARCHAR(30) DEFAULT 'pending',
    approved_at TIMESTAMP NULL,
    received_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    deleted_at TIMESTAMP NULL
);
CREATE INDEX IF NOT EXISTS order_returns_order_status_index ON order_returns(order_id, status);
CREATE INDEX IF NOT EXISTS order_returns_number_index ON order_returns(return_number);
CREATE INDEX IF NOT EXISTS order_returns_reason_index ON order_returns(reason_code);

-- Return Items
CREATE TABLE IF NOT EXISTS return_items (
    id BIGSERIAL PRIMARY KEY,
    order_return_id BIGINT NOT NULL REFERENCES order_returns(id) ON DELETE CASCADE,
    order_item_id BIGINT NOT NULL REFERENCES order_items(id) ON DELETE CASCADE,
    product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    batch_inventory_id BIGINT NULL, -- FK added after batch_inventory table created
    quantity_requested INTEGER NOT NULL,
    quantity_received INTEGER DEFAULT 0,
    quantity_approved INTEGER DEFAULT 0,
    unit_price DECIMAL(15,2) NOT NULL,
    line_total DECIMAL(15,2) NOT NULL,
    condition VARCHAR(30) NULL,
    inspection_notes TEXT NULL,
    restock BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS return_items_return_product_index ON return_items(order_return_id, product_id);
CREATE INDEX IF NOT EXISTS return_items_batch_index ON return_items(batch_inventory_id);

-- ============================================================
-- SECTION 13: LOYALTY & POINTS
-- ============================================================

-- Point Transactions
CREATE TABLE IF NOT EXISTS point_transactions (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    order_id BIGINT NULL REFERENCES orders(id) ON DELETE SET NULL,
    points INTEGER NOT NULL,
    amount INTEGER NULL,
    balance_after INTEGER NULL,
    type VARCHAR(50) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS point_transactions_user_index ON point_transactions(user_id);

-- User Loyalty Periods (from migration 000005)
CREATE TABLE IF NOT EXISTS user_loyalty_periods (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    loyalty_tier_id BIGINT NOT NULL REFERENCES loyalty_tiers(id) ON DELETE CASCADE,
    period_year SMALLINT NOT NULL,
    period_quarter SMALLINT NULL,
    period_spend DECIMAL(15,2) DEFAULT 0,
    period_orders INTEGER DEFAULT 0,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    tier_qualified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(user_id, period_year, period_quarter)
);
CREATE INDEX IF NOT EXISTS user_loyalty_periods_period_index ON user_loyalty_periods(period_year, period_quarter);

-- ============================================================
-- SECTION 14: PAYMENT LOGS
-- ============================================================

CREATE TABLE IF NOT EXISTS payment_logs (
    id BIGSERIAL PRIMARY KEY,
    order_id BIGINT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    amount DECIMAL(15,2) NOT NULL,
    method VARCHAR(255) NOT NULL,
    payment_method VARCHAR(255) NULL,
    provider VARCHAR(255) NULL,
    status VARCHAR(255) DEFAULT 'pending',
    currency VARCHAR(10) DEFAULT 'IDR',
    reference_number VARCHAR(255) NULL,
    external_id VARCHAR(255) NULL,
    proof_url VARCHAR(255) NULL,
    notes TEXT NULL,
    metadata JSONB NULL,
    verified_by BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    verified_at TIMESTAMP NULL,
    confirmed_by BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    confirmed_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS payment_logs_order_index ON payment_logs(order_id);
CREATE INDEX IF NOT EXISTS payment_logs_method_created_index ON payment_logs(method, created_at);
CREATE INDEX IF NOT EXISTS payment_logs_reference_index ON payment_logs(reference_number);

-- ============================================================
-- SECTION 15: BATCH INVENTORY (FEFO/BPOM Tracking)
-- ============================================================

CREATE TABLE IF NOT EXISTS batch_inventories (
    id BIGSERIAL PRIMARY KEY,
    product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    supplier_id BIGINT NULL REFERENCES suppliers(id) ON DELETE SET NULL,
    batch_number VARCHAR(255) NOT NULL,
    lot_number VARCHAR(255) NULL,
    quantity_received INTEGER NOT NULL DEFAULT 0,
    quantity_available INTEGER NOT NULL DEFAULT 0,
    quantity_reserved INTEGER DEFAULT 0,
    quantity_sold INTEGER DEFAULT 0,
    quantity_damaged INTEGER DEFAULT 0,
    cost_price DECIMAL(15,2) NULL,
    purchase_price DECIMAL(15,2) NULL,
    purchase_order_number VARCHAR(50) NULL,
    manufactured_at DATE NULL,
    expires_at DATE NOT NULL,
    received_at DATE NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_expired BOOLEAN DEFAULT FALSE,
    is_near_expiry BOOLEAN DEFAULT FALSE,
    near_expiry_discount_percent DECIMAL(5,2) DEFAULT 0,
    warehouse_id BIGINT NULL,
    supplier_name VARCHAR(255) NULL,
    country_of_origin VARCHAR(255) NULL,
    notes TEXT NULL,
    metadata JSONB NULL,
    deleted_at TIMESTAMPTZ NULL,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- Batch Inventories Indexes (from migration 2025_12_30_000002)
CREATE INDEX IF NOT EXISTS batch_inventories_product_expiry_index ON batch_inventories(product_id, expires_at);
CREATE INDEX IF NOT EXISTS batch_inventories_batch_expiry_index ON batch_inventories(batch_number, expires_at);
CREATE INDEX IF NOT EXISTS batch_inventories_near_expiry_index ON batch_inventories(is_near_expiry);
CREATE UNIQUE INDEX IF NOT EXISTS batch_inventories_product_batch_unique ON batch_inventories(product_id, batch_number);

-- Add FK constraint for return_items.batch_inventory_id (deferred from SECTION 12)
ALTER TABLE return_items 
    ADD CONSTRAINT fk_return_items_batch_inventory 
    FOREIGN KEY (batch_inventory_id) 
    REFERENCES batch_inventories(id) 
    ON DELETE SET NULL;

-- ============================================================
-- SECTION 16: CUSTOMER PRICE LISTS (B2B Custom Pricing)
-- ============================================================

CREATE TABLE IF NOT EXISTS customer_price_lists (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    product_id BIGINT NULL REFERENCES products(id) ON DELETE CASCADE,
    brand_id BIGINT NULL REFERENCES brands(id) ON DELETE CASCADE,
    category_id BIGINT NULL REFERENCES categories(id) ON DELETE CASCADE,
    custom_price DECIMAL(15,2) NULL,
    discount_percent DECIMAL(5,2) NULL,
    min_quantity INTEGER DEFAULT 1,
    priority INTEGER DEFAULT 0,
    valid_from DATE NULL,
    valid_until DATE NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    -- Constraint: at least one target must be set
    CONSTRAINT customer_price_lists_target_check CHECK (
        product_id IS NOT NULL OR 
        brand_id IS NOT NULL OR 
        category_id IS NOT NULL
    )
);
CREATE INDEX IF NOT EXISTS customer_price_lists_user_index ON customer_price_lists(user_id);
CREATE INDEX IF NOT EXISTS customer_price_lists_product_index ON customer_price_lists(product_id);

-- Customer Payment Terms (B2B - Net 15/30/60/90)
CREATE TABLE IF NOT EXISTS customer_payment_terms (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    -- Payment term type (from migration 2025_12_30_070737)
    term_type VARCHAR(20) DEFAULT 'cod',
    -- Credit management
    credit_limit DECIMAL(15,2) DEFAULT 0,
    current_balance DECIMAL(15,2) DEFAULT 0,
    payment_days INTEGER DEFAULT 0,
    -- Early payment incentives
    early_payment_discount_percent DECIMAL(5,2) NULL,
    early_payment_days INTEGER NULL,
    -- Approval workflow
    is_approved BOOLEAN DEFAULT FALSE,
    approved_by BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    approved_at TIMESTAMP NULL,
    -- Notes
    notes TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(user_id)
);

-- ============================================================
-- SECTION 17: CUSTOMER ORDER SETTINGS & MOQ OVERRIDES
-- ============================================================

-- Customer-level order settings
CREATE TABLE IF NOT EXISTS customer_order_settings (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    min_order_amount DECIMAL(15,2) NULL,
    min_order_units INTEGER NULL,
    default_payment_term_days SMALLINT DEFAULT 0,
    credit_limit DECIMAL(15,2) NULL,
    current_credit_used DECIMAL(15,2) DEFAULT 0,
    free_shipping_eligible BOOLEAN DEFAULT FALSE,
    free_shipping_threshold DECIMAL(15,2) NULL,
    require_po_number BOOLEAN DEFAULT FALSE,
    allow_backorder BOOLEAN DEFAULT FALSE,
    allow_partial_delivery BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(user_id)
);

-- Product MOQ overrides per customer type
CREATE TABLE IF NOT EXISTS product_moq_overrides (
    id BIGSERIAL PRIMARY KEY,
    product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    user_id BIGINT NULL REFERENCES users(id) ON DELETE CASCADE,
    loyalty_tier_id BIGINT NULL REFERENCES loyalty_tiers(id) ON DELETE CASCADE,
    customer_type VARCHAR(30) NULL,
    min_order_qty SMALLINT NOT NULL,
    order_increment SMALLINT NULL,
    max_order_qty SMALLINT NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS product_moq_overrides_product_type_index ON product_moq_overrides(product_id, customer_type);
CREATE INDEX IF NOT EXISTS product_moq_overrides_product_tier_index ON product_moq_overrides(product_id, loyalty_tier_id);

-- ============================================================
-- SECTION 18: FLEXIBLE DISCOUNT RULES SYSTEM
-- ============================================================

-- Main discount rules table
CREATE TABLE IF NOT EXISTS discount_rules (
    id BIGSERIAL PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    discount_type VARCHAR(30) NOT NULL,
    discount_value DECIMAL(15,2) DEFAULT 0,
    buy_quantity INTEGER NULL,
    get_quantity INTEGER NULL,
    get_discount_percent DECIMAL(5,2) NULL,
    min_order_amount DECIMAL(15,2) NULL,
    min_quantity INTEGER NULL,
    max_discount_amount DECIMAL(15,2) NULL,
    usage_limit INTEGER NULL,
    usage_count INTEGER DEFAULT 0,
    per_user_limit INTEGER NULL,
    product_id BIGINT NULL REFERENCES products(id) ON DELETE CASCADE,
    brand_id BIGINT NULL REFERENCES brands(id) ON DELETE CASCADE,
    category_id BIGINT NULL REFERENCES categories(id) ON DELETE CASCADE,
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
CREATE INDEX IF NOT EXISTS discount_rules_active_validity_index ON discount_rules(is_active, valid_from, valid_until);
CREATE INDEX IF NOT EXISTS discount_rules_type_index ON discount_rules(discount_type);
CREATE INDEX IF NOT EXISTS discount_rules_priority_index ON discount_rules(priority);

-- Order Discounts (track which discounts were applied)
CREATE TABLE IF NOT EXISTS order_discounts (
    id BIGSERIAL PRIMARY KEY,
    order_id BIGINT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    discount_rule_id BIGINT NULL REFERENCES discount_rules(id) ON DELETE SET NULL,
    order_item_id BIGINT NULL REFERENCES order_items(id) ON DELETE CASCADE,
    discount_type VARCHAR(30) NOT NULL,
    discount_code VARCHAR(50) NULL,
    discount_name VARCHAR(255) NOT NULL,
    original_amount DECIMAL(15,2) NOT NULL,
    discount_amount DECIMAL(15,2) NOT NULL,
    final_amount DECIMAL(15,2) NOT NULL,
    calculation_details JSONB NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS order_discounts_order_rule_index ON order_discounts(order_id, discount_rule_id);

-- ============================================================
-- SECTION 19: REVIEWS (Product Testimonials)
-- ============================================================

CREATE TABLE IF NOT EXISTS reviews (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    order_id BIGINT NULL REFERENCES orders(id) ON DELETE SET NULL,
    rating INTEGER NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(255) NULL,
    content TEXT NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    is_approved BOOLEAN DEFAULT FALSE,
    points_awarded BOOLEAN DEFAULT FALSE,
    approved_at TIMESTAMPTZ NULL,
    approved_by BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    -- Unique constraint: one review per user per product (from migration 2026_01_01_200000)
    UNIQUE (user_id, product_id)
);
CREATE INDEX IF NOT EXISTS reviews_product_approved_index ON reviews(product_id, is_approved);
CREATE INDEX IF NOT EXISTS reviews_user_index ON reviews(user_id);

-- ============================================================
-- SECTION 19B: ADDITIONAL PERFORMANCE INDEXES (from migration 2026_01_01_300000)
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
-- SECTION 20: DEFAULT SEED DATA
-- ============================================================

-- Default Loyalty Tiers
INSERT INTO loyalty_tiers (name, slug, min_spend, discount_percent, point_multiplier, free_shipping, badge_color, period_type, tier_validity_months, auto_downgrade) VALUES
('Guest', 'guest', 0, 0, 1, FALSE, '#6B7280', 'yearly', 12, TRUE),
('Bronze', 'bronze', 0, 0, 1, FALSE, '#CD7F32', 'yearly', 12, TRUE),
('Silver', 'silver', 5000000, 5, 1.25, FALSE, '#C0C0C0', 'yearly', 12, TRUE),
('Gold', 'gold', 25000000, 10, 1.5, TRUE, '#FFD700', 'yearly', 12, TRUE)
ON CONFLICT (slug) DO NOTHING;

-- Default Brands
INSERT INTO brands (name, slug, description, origin_country, is_own_brand, is_featured, sort_order, logo_url) VALUES
('Salsa Cosmetic', 'salsa-cosmetic', 'Produk hair care profesional buatan Indonesia oleh PT. Alfa Beauty Cosmetica', 'Indonesia', TRUE, TRUE, 1, 'images/brands/salsa-cosmetic.png'),
('Alfaparf Milano', 'alfaparf-milano', 'Italian professional hair care brand since 1980', 'Italy', FALSE, TRUE, 2, 'images/brands/alfaparf-milano.png'),
('Farmavita', 'farmavita', 'Professional hair color and care from Italy', 'Italy', FALSE, TRUE, 3, 'images/brands/farmavita.png'),
('Montibello', 'montibello', 'Premium Spanish professional hair care', 'Spain', FALSE, TRUE, 4, 'images/brands/montibello.png')
ON CONFLICT (slug) DO NOTHING;

-- Default Categories
INSERT INTO categories (name, slug, description, sort_order) VALUES
('Colouring', 'colouring', 'Hair color, bleach, toner, developer', 1),
('Treatment', 'treatment', 'Keratin, botox, repair treatments', 2),
('Styling', 'styling', 'Gel, wax, spray, mousse', 3),
('Care', 'care', 'Shampoo, conditioner, serum, mask', 4)
ON CONFLICT (slug) DO NOTHING;

-- Default Products
INSERT INTO products (sku, name, slug, brand_id, category_id, base_price, stock, description, is_halal, bpom_number, is_active, is_featured, images, min_order_qty, order_increment, weight_grams, selling_unit, units_per_case) VALUES
('AFP-SDL-001', 'Semi Di Lino Diamond Illuminating Serum', 'semi-di-lino-diamond-serum', 2, 4, 350000, 50, 'Serum untuk rambut berkilau seperti berlian', TRUE, 'NA18201200123', TRUE, TRUE, '["product-images/product-aurum-serum.webp"]'::jsonb, 1, 1, 45, 'bottle', 12),
('AFP-LIS-001', 'Lisse Design Keratin Therapy', 'lisse-design-keratin-therapy', 2, 2, 850000, 25, 'Keratin treatment untuk rambut lurus sempurna', TRUE, 'NA18201200124', TRUE, TRUE, '["product-images/product-lumiere-keratin.webp"]'::jsonb, 1, 1, 500, 'bottle', 6),
('SLS-SHP-001', 'Salsa Professional Keratin Shampoo', 'salsa-keratin-shampoo', 1, 4, 125000, 100, 'Shampoo keratin profesional buatan Indonesia', TRUE, 'NA18201200001', TRUE, TRUE, '["product-images/product-aurum-shampoo.webp"]'::jsonb, 6, 6, 250, 'bottle', 24),
('FMV-COL-001', 'Farmavita Suprema Color', 'farmavita-suprema-color', 3, 1, 95000, 200, 'Hair color professional dari Italia', FALSE, 'NA18201200200', TRUE, FALSE, '["product-images/product-luminoso-color.webp"]'::jsonb, 12, 6, 60, 'tube', 36),
('MTB-OLE-001', 'Montibello Oleo Intense', 'montibello-oleo-intense', 4, 4, 275000, 45, 'Premium oil treatment from Spain', TRUE, 'NA18201200301', TRUE, TRUE, '["product-images/product-alfaparf-shampoo.webp"]'::jsonb, 1, 1, 100, 'bottle', 12),
('SLS-TRT-001', 'Salsa Keratin Treatment', 'salsa-keratin-treatment', 1, 2, 185000, 75, 'Professional keratin smoothing treatment', TRUE, 'NA18201200002', TRUE, TRUE, '["product-images/product-salsa-keratin.webp"]'::jsonb, 1, 1, 500, 'bottle', 6),
('AFP-COL-001', 'Alfaparf Evolution Color', 'alfaparf-evolution-color', 2, 1, 125000, 150, 'Premium permanent hair color', TRUE, 'NA18201200125', TRUE, FALSE, '["product-images/product-luminoso-color.webp"]'::jsonb, 6, 6, 60, 'tube', 36),
('FMV-SHA-001', 'Farmavita HD Life Shampoo', 'farmavita-hd-life-shampoo', 3, 4, 165000, 80, 'Sulfate-free professional shampoo', TRUE, 'NA18201200201', TRUE, TRUE, '["product-images/product-lumiere-conditioner.webp"]'::jsonb, 1, 1, 250, 'bottle', 24)
ON CONFLICT (sku) DO NOTHING;

-- Sample Price Tiers (Volume Discounts)
INSERT INTO product_price_tiers (product_id, min_quantity, max_quantity, price, discount_percent) VALUES
(1, 1, 5, 350000, 0),
(1, 6, 11, 332500, 5),
(1, 12, NULL, 315000, 10),
(3, 6, 11, 118750, 5),
(3, 12, 23, 112500, 10),
(3, 24, NULL, 106250, 15)
ON CONFLICT DO NOTHING;

-- ============================================================
-- SECTION 21: RECORD ALL MIGRATIONS AS COMPLETE
-- ============================================================

INSERT INTO migrations (migration, batch) VALUES
('0001_01_01_000000_create_users_table', 1),
('0001_01_01_000001_create_cache_table', 1),
('0001_01_01_000002_create_jobs_table', 1),
('2025_12_28_081234_create_loyalty_tiers_table', 1),
('2025_12_28_081236_create_brands_table', 1),
('2025_12_28_081237_create_categories_table', 1),
('2025_12_28_081238_create_products_table', 1),
('2025_12_28_111825_create_orders_table', 1),
('2025_12_28_111826_create_order_items_table', 1),
('2025_12_28_111827_create_carts_table', 1),
('2025_12_28_111828_create_cart_items_table', 1),
('2025_12_28_121059_add_b2b_fields_to_users_table', 1),
('2025_12_28_130638_create_loyalty_system_tables', 1),
('2025_12_29_002500_add_discount_columns_to_orders_table', 1),
('2025_12_30_000001_create_payment_logs_table', 1),
('2025_12_30_000002_create_batch_inventory_table', 1),
('2025_12_30_062125_create_notifications_table', 1),
('2025_12_30_064135_add_batch_allocations_to_order_items_table', 1),
('2025_12_30_064708_add_price_at_add_to_cart_items_table', 1),
('2025_12_30_070735_create_customer_price_lists_table', 1),
('2025_12_30_070736_create_product_price_tiers_table', 1),
('2025_12_30_070737_create_customer_payment_terms_table', 1),
('2026_01_01_000001_add_weight_and_uom_to_products_table', 1),
('2026_01_01_000002_add_tax_columns_to_orders', 1),
('2026_01_01_000003_add_payment_tracking_to_orders', 1),
('2026_01_01_000004_create_order_returns_and_cancellations_tables', 1),
('2026_01_01_000005_add_loyalty_period_tracking', 1),
('2026_01_01_000006_fix_batch_inventory_constraint', 1),
('2026_01_01_000007_create_flexible_discount_system', 1),
('2026_01_01_000008_add_customer_moq_configuration', 1),
('2026_01_01_000009_add_customer_price_list_constraint', 1),
('2026_01_01_000010_add_product_performance_indexes', 1),
('2026_01_01_100000_create_reviews_table', 1),
('2026_01_01_200000_add_unique_constraint_to_reviews', 1),
('2026_01_01_300000_add_additional_performance_indexes', 1)
ON CONFLICT (migration) DO NOTHING;

-- ============================================================
-- DONE!
-- ============================================================

SELECT '✅ Alfa Beauty database schema created successfully!' AS status;
SELECT 'Total tables: ' || COUNT(*)::text FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE';



















