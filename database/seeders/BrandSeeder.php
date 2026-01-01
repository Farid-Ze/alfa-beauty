<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            [
                'name' => 'Salsa Cosmetic',
                'slug' => 'salsa-cosmetic',
                'logo_url' => 'images/brands/salsa-cosmetic.png',
                'description' => 'Produk hair care profesional buatan Indonesia oleh PT. Alfa Beauty Cosmetica',
                'origin_country' => 'Indonesia',
                'is_own_brand' => true,
                'is_featured' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Alfaparf Milano',
                'slug' => 'alfaparf-milano',
                'logo_url' => 'images/brands/alfaparf-milano.png',
                'description' => 'Italian professional hair care brand since 1980',
                'origin_country' => 'Italy',
                'is_own_brand' => false,
                'is_featured' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Farmavita',
                'slug' => 'farmavita',
                'logo_url' => 'images/brands/farmavita.png',
                'description' => 'Professional hair color and care from Italy',
                'origin_country' => 'Italy',
                'is_own_brand' => false,
                'is_featured' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Montibello',
                'slug' => 'montibello',
                'logo_url' => 'images/brands/montibello.png',
                'description' => 'Premium Spanish professional hair care',
                'origin_country' => 'Spain',
                'is_own_brand' => false,
                'is_featured' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($brands as $brand) {
            \App\Models\Brand::create($brand);
        }
    }
}
