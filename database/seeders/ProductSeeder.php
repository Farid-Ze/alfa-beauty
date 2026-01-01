<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            // Alfaparf Milano - Care (Serum)
            [
                'sku' => 'AFP-SDL-001',
                'name' => 'Semi Di Lino Diamond Illuminating Serum',
                'slug' => 'semi-di-lino-diamond-serum',
                'brand_id' => 2, // Alfaparf
                'category_id' => 4, // Care
                'base_price' => 350000,
                'stock' => 50,
                'description' => 'Serum untuk rambut berkilau seperti berlian',
                'is_halal' => true,
                'bpom_number' => 'NA18201200123',
                'is_active' => true,
                'is_featured' => true,
                'images' => ['product-images/product-aurum-serum.webp'],
                // Weight & Dimensions (50ml bottle)
                'weight_grams' => 85,
                'length_mm' => 35,
                'width_mm' => 35,
                'height_mm' => 120,
                // UoM & MOQ
                'selling_unit' => 'bottle',
                'units_per_case' => 12,
                'min_order_qty' => 1,
                'order_increment' => 1,
            ],
            // Alfaparf Milano - Treatment (Keratin)
            [
                'sku' => 'AFP-LIS-001',
                'name' => 'Lisse Design Keratin Therapy',
                'slug' => 'lisse-design-keratin-therapy',
                'brand_id' => 2,
                'category_id' => 2, // Treatment
                'base_price' => 850000,
                'stock' => 25,
                'description' => 'Keratin treatment untuk rambut lurus sempurna',
                'is_halal' => true,
                'bpom_number' => 'NA18201200124',
                'is_active' => true,
                'is_featured' => true,
                'images' => ['product-images/product-lumiere-keratin.webp'],
                // Weight & Dimensions (500ml bottle)
                'weight_grams' => 580,
                'length_mm' => 70,
                'width_mm' => 70,
                'height_mm' => 200,
                'selling_unit' => 'bottle',
                'units_per_case' => 6,
                'min_order_qty' => 1,
                'order_increment' => 1,
            ],
            // Salsa Cosmetic - Care (Serum)
            [
                'sku' => 'SLS-SHP-001',
                'name' => 'Salsa Professional Keratin Shampoo',
                'slug' => 'salsa-keratin-shampoo',
                'brand_id' => 1, // Salsa
                'category_id' => 4, // Care
                'base_price' => 125000,
                'stock' => 100,
                'description' => 'Shampoo keratin profesional buatan Indonesia',
                'is_halal' => true,
                'bpom_number' => 'NA18201200001',
                'is_active' => true,
                'is_featured' => true,
                'images' => ['product-images/product-aurum-shampoo.webp'],
                // Weight & Dimensions (1000ml bottle)
                'weight_grams' => 1100,
                'length_mm' => 80,
                'width_mm' => 50,
                'height_mm' => 250,
                'selling_unit' => 'bottle',
                'units_per_case' => 12,
                'min_order_qty' => 3,
                'order_increment' => 3,
            ],
            // Farmavita - Colouring (Color)
            [
                'sku' => 'FMV-COL-001',
                'name' => 'Farmavita Suprema Color',
                'slug' => 'farmavita-suprema-color',
                'brand_id' => 3, // Farmavita
                'category_id' => 1, // Colouring
                'base_price' => 95000,
                'stock' => 200,
                'description' => 'Hair color professional dari Italia',
                'is_halal' => false,
                'bpom_number' => 'NA18201200200',
                'is_active' => true,
                'is_featured' => false,
                'images' => ['product-images/product-luminoso-color.webp'],
                // Weight & Dimensions (60ml tube)
                'weight_grams' => 75,
                'length_mm' => 40,
                'width_mm' => 40,
                'height_mm' => 150,
                'selling_unit' => 'tube',
                'units_per_case' => 24,
                'min_order_qty' => 6,
                'order_increment' => 6,
            ],
            // Montibello - Care (Serum)
            [
                'sku' => 'MTB-OLE-001',
                'name' => 'Montibello Oleo Intense',
                'slug' => 'montibello-oleo-intense',
                'brand_id' => 4, // Montibello
                'category_id' => 4, // Care
                'base_price' => 275000,
                'stock' => 45,
                'description' => 'Premium oil treatment from Spain',
                'is_halal' => true,
                'bpom_number' => 'NA18201200301',
                'is_active' => true,
                'is_featured' => true,
                'images' => ['product-images/product-lumiere-conditioner.webp'],
                // Weight & Dimensions (100ml bottle)
                'weight_grams' => 140,
                'length_mm' => 40,
                'width_mm' => 40,
                'height_mm' => 140,
                'selling_unit' => 'bottle',
                'units_per_case' => 12,
                'min_order_qty' => 1,
                'order_increment' => 1,
            ],
            // Salsa Cosmetic - Treatment (Keratin)
            [
                'sku' => 'SLS-TRT-001',
                'name' => 'Salsa Keratin Treatment',
                'slug' => 'salsa-keratin-treatment',
                'brand_id' => 1, // Salsa
                'category_id' => 2, // Treatment
                'base_price' => 185000,
                'stock' => 75,
                'description' => 'Professional keratin smoothing treatment',
                'is_halal' => true,
                'bpom_number' => 'NA18201200002',
                'is_active' => true,
                'is_featured' => true,
                'images' => ['product-images/product-salsa-keratin.webp'],
                // Weight & Dimensions (250ml bottle)
                'weight_grams' => 290,
                'length_mm' => 55,
                'width_mm' => 55,
                'height_mm' => 160,
                'selling_unit' => 'bottle',
                'units_per_case' => 12,
                'min_order_qty' => 2,
                'order_increment' => 1,
            ],
            // Alfaparf Milano - Color
            [
                'sku' => 'AFP-COL-001',
                'name' => 'Alfaparf Evolution Color',
                'slug' => 'alfaparf-evolution-color',
                'brand_id' => 2, // Alfaparf
                'category_id' => 1, // Colouring
                'base_price' => 125000,
                'stock' => 150,
                'description' => 'Premium permanent hair color',
                'is_halal' => true,
                'bpom_number' => 'NA18201200125',
                'is_active' => true,
                'is_featured' => false,
                'images' => ['product-images/product-luminoso-color.webp'],
                // Weight & Dimensions (60ml tube)
                'weight_grams' => 80,
                'length_mm' => 45,
                'width_mm' => 45,
                'height_mm' => 155,
                'selling_unit' => 'tube',
                'units_per_case' => 24,
                'min_order_qty' => 6,
                'order_increment' => 6,
            ],
            // Farmavita - Care (Serum)
            [
                'sku' => 'FMV-SHA-001',
                'name' => 'Farmavita HD Life Shampoo',
                'slug' => 'farmavita-hd-life-shampoo',
                'brand_id' => 3, // Farmavita
                'category_id' => 4, // Care
                'base_price' => 165000,
                'stock' => 80,
                'description' => 'Sulfate-free professional shampoo',
                'is_halal' => true,
                'bpom_number' => 'NA18201200201',
                'is_active' => true,
                'is_featured' => true,
                'images' => ['product-images/product-alfaparf-shampoo.webp'],
                // Weight & Dimensions (350ml bottle)
                'weight_grams' => 400,
                'length_mm' => 60,
                'width_mm' => 45,
                'height_mm' => 180,
                'selling_unit' => 'bottle',
                'units_per_case' => 12,
                'min_order_qty' => 2,
                'order_increment' => 1,
            ],
        ];

        foreach ($products as $product) {
            \App\Models\Product::create($product);
        }
    }
}
