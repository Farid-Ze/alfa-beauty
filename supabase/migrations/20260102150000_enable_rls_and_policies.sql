-- Enable RLS and add policies for all public tables
-- Since Laravel uses service_role key (bypasses RLS), this is safe

-- ============================================
-- ENABLE RLS ON ALL TABLES
-- ============================================

-- Laravel internal tables
ALTER TABLE public.migrations ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.password_reset_tokens ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.sessions ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.cache ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.cache_locks ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.jobs ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.job_batches ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.failed_jobs ENABLE ROW LEVEL SECURITY;

-- User related tables
ALTER TABLE public.users ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.notifications ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.loyalty_tiers ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.user_loyalty_periods ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.point_transactions ENABLE ROW LEVEL SECURITY;

-- Product related tables
ALTER TABLE public.categories ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.brands ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.products ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.product_price_tiers ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.product_moq_overrides ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.batch_inventories ENABLE ROW LEVEL SECURITY;

-- Order related tables
ALTER TABLE public.orders ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.order_items ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.order_cancellations ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.order_returns ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.return_items ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.order_discounts ENABLE ROW LEVEL SECURITY;

-- Cart related tables
ALTER TABLE public.carts ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.cart_items ENABLE ROW LEVEL SECURITY;

-- Customer related tables
ALTER TABLE public.customer_price_lists ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.customer_payment_terms ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.customer_order_settings ENABLE ROW LEVEL SECURITY;

-- Other tables
ALTER TABLE public.suppliers ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.payment_logs ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.discount_rules ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.reviews ENABLE ROW LEVEL SECURITY;

-- ============================================
-- RLS POLICIES
-- ============================================

-- INTERNAL TABLES (No direct API access - Laravel only)
CREATE POLICY "Deny direct access migrations" ON public.migrations FOR ALL USING (false);
CREATE POLICY "Deny direct access password_reset_tokens" ON public.password_reset_tokens FOR ALL USING (false);
CREATE POLICY "Deny direct access sessions" ON public.sessions FOR ALL USING (false);
CREATE POLICY "Deny direct access cache" ON public.cache FOR ALL USING (false);
CREATE POLICY "Deny direct access cache_locks" ON public.cache_locks FOR ALL USING (false);
CREATE POLICY "Deny direct access jobs" ON public.jobs FOR ALL USING (false);
CREATE POLICY "Deny direct access job_batches" ON public.job_batches FOR ALL USING (false);
CREATE POLICY "Deny direct access failed_jobs" ON public.failed_jobs FOR ALL USING (false);
CREATE POLICY "Deny direct access batch_inventories" ON public.batch_inventories FOR ALL USING (false);
CREATE POLICY "Deny direct access suppliers" ON public.suppliers FOR ALL USING (false);

-- PUBLIC READ TABLES
CREATE POLICY "Public read categories" ON public.categories FOR SELECT USING (true);
CREATE POLICY "Public read brands" ON public.brands FOR SELECT USING (true);
CREATE POLICY "Public read products" ON public.products FOR SELECT USING (is_active = true);
CREATE POLICY "Public read loyalty_tiers" ON public.loyalty_tiers FOR SELECT USING (true);
CREATE POLICY "Public read product_price_tiers" ON public.product_price_tiers FOR SELECT USING (true);
CREATE POLICY "Public read active discount_rules" ON public.discount_rules FOR SELECT USING (is_active = true);
CREATE POLICY "Public read approved reviews" ON public.reviews FOR SELECT USING (is_approved = true);

-- USER DATA (Own data only)
CREATE POLICY "Users view own profile" ON public.users FOR SELECT USING (auth.uid()::text = id::text);
CREATE POLICY "Users view own notifications" ON public.notifications FOR SELECT USING (auth.uid()::text = notifiable_id::text AND notifiable_type = 'App\Models\User');
CREATE POLICY "Users view own point_transactions" ON public.point_transactions FOR SELECT USING (auth.uid()::text = user_id::text);
CREATE POLICY "Users view own user_loyalty_periods" ON public.user_loyalty_periods FOR SELECT USING (auth.uid()::text = user_id::text);
CREATE POLICY "Users view own product_moq_overrides" ON public.product_moq_overrides FOR SELECT USING (auth.uid()::text = user_id::text);
CREATE POLICY "Users view own customer_price_lists" ON public.customer_price_lists FOR SELECT USING (auth.uid()::text = user_id::text);
CREATE POLICY "Users view own customer_payment_terms" ON public.customer_payment_terms FOR SELECT USING (auth.uid()::text = user_id::text);
CREATE POLICY "Users view own customer_order_settings" ON public.customer_order_settings FOR SELECT USING (auth.uid()::text = user_id::text);

-- ORDERS (Own orders only)
CREATE POLICY "Users view own orders" ON public.orders FOR SELECT USING (auth.uid()::text = user_id::text);
CREATE POLICY "Users view own order_items" ON public.order_items FOR SELECT USING (EXISTS (SELECT 1 FROM public.orders WHERE orders.id = order_items.order_id AND orders.user_id::text = auth.uid()::text));
CREATE POLICY "Users view own order_cancellations" ON public.order_cancellations FOR SELECT USING (EXISTS (SELECT 1 FROM public.orders WHERE orders.id = order_cancellations.order_id AND orders.user_id::text = auth.uid()::text));
CREATE POLICY "Users view own order_returns" ON public.order_returns FOR SELECT USING (EXISTS (SELECT 1 FROM public.orders WHERE orders.id = order_returns.order_id AND orders.user_id::text = auth.uid()::text));
CREATE POLICY "Users view own return_items" ON public.return_items FOR SELECT USING (EXISTS (SELECT 1 FROM public.order_returns r JOIN public.orders o ON o.id = r.order_id WHERE r.id = return_items.order_return_id AND o.user_id::text = auth.uid()::text));
CREATE POLICY "Users view own order_discounts" ON public.order_discounts FOR SELECT USING (EXISTS (SELECT 1 FROM public.orders WHERE orders.id = order_discounts.order_id AND orders.user_id::text = auth.uid()::text));
CREATE POLICY "Users view own payment_logs" ON public.payment_logs FOR SELECT USING (EXISTS (SELECT 1 FROM public.orders WHERE orders.id = payment_logs.order_id AND orders.user_id::text = auth.uid()::text));

-- CARTS (Full CRUD for own cart)
CREATE POLICY "Users select own carts" ON public.carts FOR SELECT USING (auth.uid()::text = user_id::text);
CREATE POLICY "Users insert own carts" ON public.carts FOR INSERT WITH CHECK (auth.uid()::text = user_id::text);
CREATE POLICY "Users update own carts" ON public.carts FOR UPDATE USING (auth.uid()::text = user_id::text);
CREATE POLICY "Users delete own carts" ON public.carts FOR DELETE USING (auth.uid()::text = user_id::text);

CREATE POLICY "Users select own cart_items" ON public.cart_items FOR SELECT USING (EXISTS (SELECT 1 FROM public.carts WHERE carts.id = cart_items.cart_id AND carts.user_id::text = auth.uid()::text));
CREATE POLICY "Users insert own cart_items" ON public.cart_items FOR INSERT WITH CHECK (EXISTS (SELECT 1 FROM public.carts WHERE carts.id = cart_items.cart_id AND carts.user_id::text = auth.uid()::text));
CREATE POLICY "Users update own cart_items" ON public.cart_items FOR UPDATE USING (EXISTS (SELECT 1 FROM public.carts WHERE carts.id = cart_items.cart_id AND carts.user_id::text = auth.uid()::text));
CREATE POLICY "Users delete own cart_items" ON public.cart_items FOR DELETE USING (EXISTS (SELECT 1 FROM public.carts WHERE carts.id = cart_items.cart_id AND carts.user_id::text = auth.uid()::text));

-- REVIEWS (Users can manage their own)
CREATE POLICY "Users insert own reviews" ON public.reviews FOR INSERT WITH CHECK (auth.uid()::text = user_id::text);
CREATE POLICY "Users update own reviews" ON public.reviews FOR UPDATE USING (auth.uid()::text = user_id::text);
CREATE POLICY "Users delete own reviews" ON public.reviews FOR DELETE USING (auth.uid()::text = user_id::text);
