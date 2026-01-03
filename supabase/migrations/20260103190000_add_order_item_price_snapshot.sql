-- Add price snapshot isolation columns to order_items
-- Mirrors Laravel migration: 2026_01_03_100001_add_price_snapshot_to_order_items

BEGIN;

ALTER TABLE order_items
  ADD COLUMN IF NOT EXISTS price_locked_at TIMESTAMP NULL;

ALTER TABLE order_items
  ADD COLUMN IF NOT EXISTS pricing_metadata JSONB NULL;

-- Backfill to ensure historical immutability baseline
UPDATE order_items
SET price_locked_at = created_at
WHERE price_locked_at IS NULL;

-- Optional index for audit queries
CREATE INDEX IF NOT EXISTS idx_order_items_price_locked_at ON order_items(price_locked_at);

COMMIT;
