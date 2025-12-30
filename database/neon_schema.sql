-- ================================================
-- Alfa Beauty PostgreSQL Schema for Neon
-- Generated from Laravel migrations
-- Run this in Neon SQL Editor
-- ================================================

-- Migrations tracking table
CREATE TABLE IF NOT EXISTS migrations (
    id SERIAL PRIMARY KEY,
    migration VARCHAR(255) NOT NULL,
    batch INTEGER NOT NULL
);

-- ================================================
-- CORE LARAVEL TABLES
-- ================================================

-- Users table
CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Password reset tokens
CREATE TABLE password_reset_tokens (
    email VARCHAR(255) PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL
);

-- Sessions table
CREATE TABLE sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    payload TEXT NOT NULL,
    last_activity INTEGER NOT NULL
);
CREATE INDEX sessions_user_id_index ON sessions(user_id);
CREATE INDEX sessions_last_activity_index ON sessions(last_activity);

-- Cache table
CREATE TABLE cache (
    key VARCHAR(255) PRIMARY KEY,
    value TEXT NOT NULL,
    expiration INTEGER NOT NULL
);

-- Cache locks
CREATE TABLE cache_locks (
    key VARCHAR(255) PRIMARY KEY,
    owner VARCHAR(255) NOT NULL,
    expiration INTEGER NOT NULL
);

-- Jobs table
CREATE TABLE jobs (
    id BIGSERIAL PRIMARY KEY,
    queue VARCHAR(255) NOT NULL,
    payload TEXT NOT NULL,
    attempts SMALLINT NOT NULL,
    reserved_at INTEGER NULL,
    available_at INTEGER NOT NULL,
    created_at INTEGER NOT NULL
);
CREATE INDEX jobs_queue_index ON jobs(queue);

-- Job batches
CREATE TABLE job_batches (
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
CREATE TABLE failed_jobs (
    id BIGSERIAL PRIMARY KEY,
    uuid VARCHAR(255) UNIQUE NOT NULL,
    connection TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload TEXT NOT NULL,
    exception TEXT NOT NULL,
    failed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL
);

-- Notifications
CREATE TABLE notifications (
    id UUID PRIMARY KEY,
    type VARCHAR(255) NOT NULL,
    notifiable_type VARCHAR(255) NOT NULL,
    notifiable_id BIGINT NOT NULL,
    data TEXT NOT NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
CREATE INDEX notifications_notifiable_type_notifiable_id_index ON notifications(notifiable_type, notifiable_id);

-- ================================================
-- BUSINESS TABLES
-- ================================================

-- Loyalty tiers
CREATE TABLE loyalty_tiers (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    min_spend DECIMAL(15,2) DEFAULT 0,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    point_multiplier DECIMAL(3,2) DEFAULT 1,
    free_shipping BOOLEAN DEFAULT FALSE,
    badge_color VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Brands
CREATE TABLE brands (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    logo_url VARCHAR(255) NULL,
    description TEXT NULL,
    origin_country VARCHAR(255) NULL,
    is_own_brand BOOLEAN DEFAULT FALSE,
    is_featured BOOLEAN DEFAULT FALSE,
    sort_order INTEGER DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Categories
CREATE TABLE categories (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    parent_id BIGINT NULL REFERENCES categories(id) ON DELETE SET NULL,
    description TEXT NULL,
    icon VARCHAR(255) NULL,
    sort_order INTEGER DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Products
CREATE TABLE products (
    id BIGSERIAL PRIMARY KEY,
    sku VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    brand_id BIGINT NOT NULL REFERENCES brands(id) ON DELETE CASCADE,
    category_id BIGINT NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
    base_price DECIMAL(15,2) NOT NULL,
    stock INTEGER DEFAULT 0,
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
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
CREATE INDEX products_sku_index ON products(sku);
CREATE INDEX products_brand_id_is_active_index ON products(brand_id, is_active);
CREATE INDEX products_category_id_is_active_index ON products(category_id, is_active);

-- Orders
CREATE TABLE orders (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    order_number VARCHAR(255) UNIQUE NOT NULL,
    status VARCHAR(255) DEFAULT 'pending',
    total_amount DECIMAL(15,2) NOT NULL,
    payment_method VARCHAR(255) NULL,
    payment_status VARCHAR(255) DEFAULT 'unpaid',
    shipping_address TEXT NULL,
    shipping_method VARCHAR(255) NULL,
    shipping_cost DECIMAL(15,2) DEFAULT 0,
    notes TEXT NULL,
    -- Added columns from later migrations
    subtotal DECIMAL(15,2) DEFAULT 0,
    discount_amount DECIMAL(15,2) DEFAULT 0,
    discount_source VARCHAR(255) NULL,
    tier_discount_percent DECIMAL(5,2) DEFAULT 0,
    customer_name VARCHAR(255) NULL,
    customer_phone VARCHAR(255) NULL,
    points_earned INTEGER DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Order items
CREATE TABLE order_items (
    id BIGSERIAL PRIMARY KEY,
    order_id BIGINT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    quantity INTEGER NOT NULL,
    unit_price DECIMAL(15,2) NOT NULL,
    total_price DECIMAL(15,2) NOT NULL,
    batch_allocations JSONB NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Carts
CREATE TABLE carts (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NULL REFERENCES users(id) ON DELETE CASCADE,
    session_id VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Cart items
CREATE TABLE cart_items (
    id BIGSERIAL PRIMARY KEY,
    cart_id BIGINT NOT NULL REFERENCES carts(id) ON DELETE CASCADE,
    product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    quantity INTEGER DEFAULT 1,
    price_at_add DECIMAL(15,2) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- ================================================
-- B2B & LOYALTY TABLES
-- ================================================

-- Add B2B fields to users (already in users table creation above, adding alter for clarity)
ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS business_name VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS business_type VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS npwp VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS loyalty_tier_id BIGINT NULL REFERENCES loyalty_tiers(id) ON DELETE SET NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS total_points INTEGER DEFAULT 0;
ALTER TABLE users ADD COLUMN IF NOT EXISTS annual_spend DECIMAL(15,2) DEFAULT 0;
ALTER TABLE users ADD COLUMN IF NOT EXISTS address TEXT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS city VARCHAR(255) NULL;

-- Point transactions
CREATE TABLE point_transactions (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    order_id BIGINT NULL REFERENCES orders(id) ON DELETE SET NULL,
    points INTEGER NOT NULL,
    type VARCHAR(255) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Payment logs
CREATE TABLE payment_logs (
    id BIGSERIAL PRIMARY KEY,
    order_id BIGINT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    amount DECIMAL(15,2) NOT NULL,
    method VARCHAR(255) NOT NULL,
    status VARCHAR(255) DEFAULT 'pending',
    reference_number VARCHAR(255) NULL,
    proof_url VARCHAR(255) NULL,
    notes TEXT NULL,
    verified_by BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Batch inventory (FEFO tracking)
CREATE TABLE batch_inventories (
    id BIGSERIAL PRIMARY KEY,
    product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    batch_number VARCHAR(255) NOT NULL,
    quantity_received INTEGER NOT NULL,
    quantity_available INTEGER NOT NULL,
    quantity_reserved INTEGER DEFAULT 0,
    cost_price DECIMAL(15,2) NULL,
    manufactured_at DATE NULL,
    expires_at DATE NOT NULL,
    received_at DATE NOT NULL,
    is_near_expiry BOOLEAN DEFAULT FALSE,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
CREATE INDEX batch_inventories_product_id_expires_at_index ON batch_inventories(product_id, expires_at);
CREATE UNIQUE INDEX batch_inventories_product_id_batch_number_unique ON batch_inventories(product_id, batch_number);

-- Customer price lists
CREATE TABLE customer_price_lists (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    custom_price DECIMAL(15,2) NOT NULL,
    valid_from DATE NULL,
    valid_until DATE NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
CREATE UNIQUE INDEX customer_price_lists_user_product_unique ON customer_price_lists(user_id, product_id);

-- Product price tiers
CREATE TABLE product_price_tiers (
    id BIGSERIAL PRIMARY KEY,
    product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    min_quantity INTEGER NOT NULL,
    price DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
CREATE INDEX product_price_tiers_product_id_index ON product_price_tiers(product_id);

-- Customer payment terms
CREATE TABLE customer_payment_terms (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    credit_limit DECIMAL(15,2) DEFAULT 0,
    payment_days INTEGER DEFAULT 0,
    current_balance DECIMAL(15,2) DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
CREATE UNIQUE INDEX customer_payment_terms_user_id_unique ON customer_payment_terms(user_id);

-- ================================================
-- RECORD MIGRATIONS AS COMPLETE
-- ================================================
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
('2025_12_30_070737_create_customer_payment_terms_table', 1);

-- ================================================
-- INSERT DEFAULT DATA
-- ================================================

-- Default loyalty tiers
INSERT INTO loyalty_tiers (name, slug, min_spend, discount_percent, point_multiplier, free_shipping, badge_color, created_at, updated_at) VALUES
('Guest', 'guest', 0, 0, 1, FALSE, '#808080', NOW(), NOW()),
('Silver', 'silver', 5000000, 5, 1.5, FALSE, '#C0C0C0', NOW(), NOW()),
('Gold', 'gold', 25000000, 10, 2, TRUE, '#C9A962', NOW(), NOW()),
('Platinum', 'platinum', 100000000, 15, 3, TRUE, '#E5E4E2', NOW(), NOW());

-- Done!
SELECT 'Schema and default data created successfully!' AS status;
