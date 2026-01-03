-- Add indexes for unindexed foreign keys to improve query performance
-- These indexes help with JOINs, DELETEs, and lookups on foreign key columns

-- batch_inventories
CREATE INDEX IF NOT EXISTS idx_batch_inventories_supplier ON public.batch_inventories(supplier_id);

-- categories (self-referencing for hierarchy)
CREATE INDEX IF NOT EXISTS idx_categories_parent ON public.categories(parent_id);

-- customer_payment_terms
CREATE INDEX IF NOT EXISTS idx_customer_payment_terms_approved_by ON public.customer_payment_terms(approved_by);

-- customer_price_lists
CREATE INDEX IF NOT EXISTS idx_customer_price_lists_brand ON public.customer_price_lists(brand_id);
CREATE INDEX IF NOT EXISTS idx_customer_price_lists_category ON public.customer_price_lists(category_id);

-- discount_rules
CREATE INDEX IF NOT EXISTS idx_discount_rules_brand ON public.discount_rules(brand_id);
CREATE INDEX IF NOT EXISTS idx_discount_rules_category ON public.discount_rules(category_id);
CREATE INDEX IF NOT EXISTS idx_discount_rules_product ON public.discount_rules(product_id);

-- order_cancellations
CREATE INDEX IF NOT EXISTS idx_order_cancellations_cancelled_by ON public.order_cancellations(cancelled_by);

-- order_discounts
CREATE INDEX IF NOT EXISTS idx_order_discounts_rule ON public.order_discounts(discount_rule_id);
CREATE INDEX IF NOT EXISTS idx_order_discounts_item ON public.order_discounts(order_item_id);

-- order_returns
CREATE INDEX IF NOT EXISTS idx_order_returns_processed_by ON public.order_returns(processed_by);

-- payment_logs
CREATE INDEX IF NOT EXISTS idx_payment_logs_confirmed_by ON public.payment_logs(confirmed_by);
CREATE INDEX IF NOT EXISTS idx_payment_logs_verified_by ON public.payment_logs(verified_by);

-- point_transactions
CREATE INDEX IF NOT EXISTS idx_point_transactions_order ON public.point_transactions(order_id);

-- product_moq_overrides
CREATE INDEX IF NOT EXISTS idx_product_moq_overrides_tier ON public.product_moq_overrides(loyalty_tier_id);
CREATE INDEX IF NOT EXISTS idx_product_moq_overrides_user ON public.product_moq_overrides(user_id);

-- return_items
CREATE INDEX IF NOT EXISTS idx_return_items_order_item ON public.return_items(order_item_id);
CREATE INDEX IF NOT EXISTS idx_return_items_product ON public.return_items(product_id);

-- reviews
CREATE INDEX IF NOT EXISTS idx_reviews_approved_by ON public.reviews(approved_by);
CREATE INDEX IF NOT EXISTS idx_reviews_order ON public.reviews(order_id);

-- user_loyalty_periods
CREATE INDEX IF NOT EXISTS idx_user_loyalty_periods_tier ON public.user_loyalty_periods(loyalty_tier_id);
