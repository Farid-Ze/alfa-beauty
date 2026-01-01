<?php

namespace Database\Seeders;

use App\Models\DiscountRule;
use App\Models\Brand;
use App\Models\Category;
use App\Models\LoyaltyTier;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DiscountRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        
        // Get references
        $goldTier = LoyaltyTier::where('slug', 'gold')->first();
        $platinumTier = LoyaltyTier::where('slug', 'platinum')->first();
        $shampooCategory = Category::where('slug', 'shampoo')->first();
        
        $discountRules = [
            // 1. New Customer Discount - 5% off first order
            [
                'code' => 'NEWCUSTOMER',
                'name' => 'Diskon Pelanggan Baru',
                'description' => 'Potongan 5% untuk pembelian pertama. Berlaku untuk semua produk.',
                'discount_type' => DiscountRule::TYPE_PERCENTAGE,
                'discount_value' => 5.00,
                'min_order_amount' => 500000,
                'max_discount_amount' => 100000,
                'is_active' => true,
                'is_stackable' => false,
                'priority' => 10,
                'usage_limit' => null,
                'per_user_limit' => 1,
                'valid_from' => $now,
                'valid_until' => $now->copy()->addYear(),
            ],
            
            // 2. Volume Discount - Rp 50.000 off for orders > Rp 2.000.000
            [
                'code' => 'VOLUME50K',
                'name' => 'Diskon Volume Pembelian',
                'description' => 'Potongan Rp 50.000 untuk pesanan minimal Rp 2.000.000',
                'discount_type' => DiscountRule::TYPE_FIXED_AMOUNT,
                'discount_value' => 50000,
                'min_order_amount' => 2000000,
                'max_discount_amount' => null,
                'is_active' => true,
                'is_stackable' => true,
                'priority' => 20,
                'usage_limit' => null,
                'per_user_limit' => null,
                'valid_from' => $now,
                'valid_until' => null,
            ],
            
            // 3. Buy 5 Get 1 Shampoo
            [
                'code' => 'BUY5GET1',
                'name' => 'Beli 5 Shampoo Gratis 1',
                'description' => 'Beli 5 botol shampoo merek apapun, gratis 1 botol (nilai terendah)',
                'discount_type' => DiscountRule::TYPE_BUY_X_GET_Y,
                'discount_value' => 100, // 100% off = free
                'buy_quantity' => 5,
                'get_quantity' => 1,
                'get_discount_percent' => 100,
                'min_quantity' => 5,
                'category_id' => $shampooCategory?->id,
                'is_active' => true,
                'is_stackable' => false,
                'priority' => 30,
                'valid_from' => $now,
                'valid_until' => $now->copy()->addMonths(3),
            ],
            
            // 4. Gold Member Exclusive - Extra 2%
            [
                'code' => 'GOLDBONUS',
                'name' => 'Bonus Eksklusif Member Gold',
                'description' => 'Diskon tambahan 2% khusus member Gold di atas diskon tier normal',
                'discount_type' => DiscountRule::TYPE_PERCENTAGE,
                'discount_value' => 2.00,
                'min_order_amount' => 1000000,
                'loyalty_tier_ids' => $goldTier ? [$goldTier->id] : null,
                'is_active' => true,
                'is_stackable' => true,
                'priority' => 40,
                'valid_from' => $now,
                'valid_until' => null,
            ],
            
            // 5. Platinum Member Exclusive - Extra 3%
            [
                'code' => 'PLATBONUS',
                'name' => 'Bonus Eksklusif Member Platinum',
                'description' => 'Diskon tambahan 3% khusus member Platinum di atas diskon tier normal',
                'discount_type' => DiscountRule::TYPE_PERCENTAGE,
                'discount_value' => 3.00,
                'min_order_amount' => 1000000,
                'loyalty_tier_ids' => $platinumTier ? [$platinumTier->id] : null,
                'is_active' => true,
                'is_stackable' => true,
                'priority' => 41,
                'valid_from' => $now,
                'valid_until' => null,
            ],
            
            // 6. Flash Sale - Weekend Special
            [
                'code' => 'WEEKEND15',
                'name' => 'Flash Sale Weekend',
                'description' => 'Diskon spesial 15% setiap akhir pekan. Gunakan kode WEEKEND15',
                'discount_type' => DiscountRule::TYPE_PERCENTAGE,
                'discount_value' => 15.00,
                'min_order_amount' => 750000,
                'max_discount_amount' => 200000,
                'is_active' => true,
                'is_stackable' => false,
                'priority' => 50,
                'usage_limit' => 100,
                'per_user_limit' => 2,
                'valid_from' => $now,
                'valid_until' => $now->copy()->addMonth(),
            ],
            
            // 7. Bundle Price - Starter Kit
            [
                'code' => 'STARTERKIT',
                'name' => 'Paket Salon Starter Kit',
                'description' => 'Beli 3 produk berbeda dari kategori berbeda, dapatkan harga paket Rp 450.000',
                'discount_type' => DiscountRule::TYPE_BUNDLE_PRICE,
                'discount_value' => 450000,
                'min_quantity' => 3,
                'is_active' => true,
                'is_stackable' => false,
                'priority' => 60,
                'valid_from' => $now,
                'valid_until' => null,
            ],
            
            // 8. Referral Discount
            [
                'code' => 'REFERRAL75K',
                'name' => 'Diskon Referral',
                'description' => 'Dapatkan Rp 75.000 untuk setiap referral yang berhasil',
                'discount_type' => DiscountRule::TYPE_FIXED_AMOUNT,
                'discount_value' => 75000,
                'min_order_amount' => 500000,
                'is_active' => true,
                'is_stackable' => true,
                'priority' => 70,
                'usage_limit' => null,
                'per_user_limit' => 1,
                'valid_from' => $now,
                'valid_until' => null,
            ],
        ];

        foreach ($discountRules as $rule) {
            DiscountRule::updateOrCreate(
                ['code' => $rule['code']],
                $rule
            );
        }
    }
}
