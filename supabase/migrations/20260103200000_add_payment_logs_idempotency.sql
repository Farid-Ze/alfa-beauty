-- Date: 2026-01-03
-- Purpose: Add deterministic idempotency + request correlation to payment_logs.
-- Focus: Best practices, idempotency, traceability, governance, and data integrity.

BEGIN;

ALTER TABLE public.payment_logs
    ADD COLUMN IF NOT EXISTS request_id uuid,
    ADD COLUMN IF NOT EXISTS idempotency_key varchar(128);

CREATE INDEX IF NOT EXISTS idx_payment_logs_request_id ON public.payment_logs(request_id);
CREATE INDEX IF NOT EXISTS idx_payment_logs_idempotency_key ON public.payment_logs(idempotency_key);

-- Defensive dedupe for (order_id, idempotency_key)
DELETE FROM public.payment_logs a
USING public.payment_logs b
WHERE a.order_id = b.order_id
  AND a.idempotency_key IS NOT NULL
  AND b.idempotency_key = a.idempotency_key
  AND b.id < a.id;

-- Defensive dedupe for (order_id, reference_number)
DELETE FROM public.payment_logs a
USING public.payment_logs b
WHERE a.order_id = b.order_id
  AND a.reference_number IS NOT NULL
  AND b.reference_number = a.reference_number
  AND b.id < a.id;

-- Defensive dedupe for external_id
DELETE FROM public.payment_logs a
USING public.payment_logs b
WHERE a.external_id IS NOT NULL
  AND b.external_id = a.external_id
  AND b.id < a.id;

CREATE UNIQUE INDEX IF NOT EXISTS payment_logs_order_idempotency_unique
    ON public.payment_logs(order_id, idempotency_key);

CREATE UNIQUE INDEX IF NOT EXISTS payment_logs_order_reference_unique
    ON public.payment_logs(order_id, reference_number);

CREATE UNIQUE INDEX IF NOT EXISTS payment_logs_external_id_unique
    ON public.payment_logs(external_id);

COMMIT;
