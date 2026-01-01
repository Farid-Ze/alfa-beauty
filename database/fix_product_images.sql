-- ============================================================
-- FIX PRODUCT IMAGE PATHS (UPDATE ONLY - SAFE)
-- Run this in Supabase SQL Editor
-- ============================================================

-- Update image paths from old format to new format
-- Old: products/*.png → New: product-images/*.webp

UPDATE products SET images = '["product-images/product-aurum-serum.webp"]'::jsonb WHERE sku = 'AFP-SDL-001';
UPDATE products SET images = '["product-images/product-lumiere-keratin.webp"]'::jsonb WHERE sku = 'AFP-LIS-001';
UPDATE products SET images = '["product-images/product-aurum-shampoo.webp"]'::jsonb WHERE sku = 'SLS-SHP-001';
UPDATE products SET images = '["product-images/product-luminoso-color.webp"]'::jsonb WHERE sku = 'FMV-COL-001';
UPDATE products SET images = '["product-images/product-alfaparf-shampoo.webp"]'::jsonb WHERE sku = 'MTB-OLE-001';
UPDATE products SET images = '["product-images/product-salsa-keratin.webp"]'::jsonb WHERE sku = 'SLS-TRT-001';
UPDATE products SET images = '["product-images/product-luminoso-color.webp"]'::jsonb WHERE sku = 'AFP-COL-001';
UPDATE products SET images = '["product-images/product-lumiere-conditioner.webp"]'::jsonb WHERE sku = 'FMV-SHA-001';

-- Verify
SELECT sku, name, images FROM products ORDER BY sku;
