-- Date: 2026-01-03
-- Purpose: Bring Supabase (Postgres) schema up-to-date with latest Laravel hardening
-- Focus: Best practices, idempotency, traceability, governance/auditability, and data integrity.
--
-- Notes:
-- - This migration is written to be idempotent (safe to re-run).
-- - It uses Postgres-compatible dedupe patterns (DELETE ... USING / window functions).

BEGIN;

-- ============================================================
-- 1) orders: request correlation + idempotency key
-- ============================================================
ALTER TABLE public.orders
    ADD COLUMN IF NOT EXISTS request_id uuid,
    ADD COLUMN IF NOT EXISTS idempotency_key varchar(128);

-- Defensive dedupe: keep earliest row, nullify duplicate keys.
WITH dups AS (
    SELECT id,
           row_number() OVER (PARTITION BY idempotency_key ORDER BY id ASC) AS rn
    FROM public.orders
    WHERE idempotency_key IS NOT NULL
)
UPDATE public.orders o
SET idempotency_key = NULL
FROM dups d
WHERE o.id = d.id
  AND d.rn > 1;

CREATE INDEX IF NOT EXISTS idx_orders_request_id ON public.orders(request_id);
CREATE UNIQUE INDEX IF NOT EXISTS orders_idempotency_key_unique ON public.orders(idempotency_key);

-- ============================================================
-- 2) order_items: pricing provenance / traceability
-- ============================================================
ALTER TABLE public.order_items
    ADD COLUMN IF NOT EXISTS price_source varchar(255),
    ADD COLUMN IF NOT EXISTS original_unit_price numeric(15,2),
    ADD COLUMN IF NOT EXISTS discount_percent numeric(5,2),
    ADD COLUMN IF NOT EXISTS pricing_meta jsonb;

-- ============================================================
-- 3) point_transactions: request + idempotency + balance
-- ============================================================
ALTER TABLE public.point_transactions
    ADD COLUMN IF NOT EXISTS request_id uuid,
    ADD COLUMN IF NOT EXISTS idempotency_key varchar(191),
    ADD COLUMN IF NOT EXISTS balance_after integer;

-- Defensive dedupe: keep earliest row per idempotency_key.
DELETE FROM public.point_transactions a
USING public.point_transactions b
WHERE a.idempotency_key IS NOT NULL
  AND b.idempotency_key = a.idempotency_key
  AND b.id < a.id;

CREATE UNIQUE INDEX IF NOT EXISTS uniq_point_transactions_idempotency_key
    ON public.point_transactions(idempotency_key);

CREATE INDEX IF NOT EXISTS idx_point_transactions_request_id
    ON public.point_transactions(request_id);

-- ============================================================
-- 4) audit_events: governance event log + idempotency
-- ============================================================
CREATE TABLE IF NOT EXISTS public.audit_events (
    id BIGSERIAL PRIMARY KEY,
    request_id uuid NULL,
    idempotency_key varchar(128) NULL,
    actor_user_id BIGINT NULL REFERENCES public.users(id) ON DELETE SET NULL,
    action varchar(64) NOT NULL,
    entity_type varchar(128) NOT NULL,
    entity_id BIGINT NULL,
    meta jsonb NULL,
    created_at timestamp DEFAULT NOW(),
    updated_at timestamp DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_audit_events_request_id
    ON public.audit_events(request_id);

CREATE INDEX IF NOT EXISTS idx_audit_events_idempotency_key
    ON public.audit_events(idempotency_key);

CREATE INDEX IF NOT EXISTS idx_audit_events_entity
    ON public.audit_events(entity_type, entity_id);

-- Defensive dedupe: keep earliest row per idempotency_key.
DELETE FROM public.audit_events a
USING public.audit_events b
WHERE a.idempotency_key IS NOT NULL
  AND b.idempotency_key = a.idempotency_key
  AND b.id < a.id;

CREATE UNIQUE INDEX IF NOT EXISTS audit_events_idempotency_key_unique
    ON public.audit_events(idempotency_key);

-- ============================================================
-- 5) order_cancellations: once-only inventory release marker + integrity
-- ============================================================
ALTER TABLE public.order_cancellations
    ADD COLUMN IF NOT EXISTS inventory_released_at timestamp NULL;

-- Defensive dedupe: keep earliest cancellation row per order.
DELETE FROM public.order_cancellations a
USING public.order_cancellations b
WHERE a.order_id = b.order_id
  AND b.id < a.id;

CREATE UNIQUE INDEX IF NOT EXISTS order_cancellations_order_id_unique
    ON public.order_cancellations(order_id);

CREATE INDEX IF NOT EXISTS idx_order_cancellations_inventory_released_at
    ON public.order_cancellations(inventory_released_at);

-- ============================================================
-- 6) order_returns: once-only restock marker + loyalty reversal marker
-- ============================================================
ALTER TABLE public.order_returns
    ADD COLUMN IF NOT EXISTS inventory_restocked_at timestamp NULL,
    ADD COLUMN IF NOT EXISTS loyalty_reversed_at timestamp NULL;

CREATE INDEX IF NOT EXISTS idx_order_returns_inventory_restocked_at
    ON public.order_returns(inventory_restocked_at);

CREATE INDEX IF NOT EXISTS idx_order_returns_loyalty_reversed_at
    ON public.order_returns(loyalty_reversed_at);

-- ============================================================
-- 7) return_items: enforce one row per (return, order_item)
-- ============================================================
DELETE FROM public.return_items a
USING public.return_items b
WHERE a.order_return_id = b.order_return_id
  AND a.order_item_id = b.order_item_id
  AND b.id < a.id;

CREATE UNIQUE INDEX IF NOT EXISTS return_items_order_return_order_item_unique
    ON public.return_items(order_return_id, order_item_id);

COMMIT;
