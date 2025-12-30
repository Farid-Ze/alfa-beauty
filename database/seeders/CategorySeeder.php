<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Colouring',
                'slug' => 'colouring',
                'description' => 'Hair color, bleach, toner, developer',
                'sort_order' => 1,
            ],
            [
                'name' => 'Treatment',
                'slug' => 'treatment',
                'description' => 'Keratin, botox, repair treatments',
                'sort_order' => 2,
            ],
            [
                'name' => 'Styling',
                'slug' => 'styling',
                'description' => 'Gel, wax, spray, mousse',
                'sort_order' => 3,
            ],
            [
                'name' => 'Care',
                'slug' => 'care',
                'description' => 'Shampoo, conditioner, serum, mask',
                'sort_order' => 4,
            ],
        ];

        foreach ($categories as $category) {
            \App\Models\Category::create($category);
        }
    }
}
