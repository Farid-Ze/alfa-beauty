<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(3, true) . ' Treatment',
            'slug' => $this->faker->unique()->slug(),
            'sku' => strtoupper($this->faker->unique()->bothify('PRD-????-####')),
            'description' => $this->faker->paragraphs(2, true),
            'bpom_number' => 'NA' . $this->faker->numerify('##########'),
            'base_price' => $this->faker->randomElement([25000, 50000, 75000, 100000, 150000, 200000]),
            'stock' => $this->faker->numberBetween(0, 500),
            'brand_id' => Brand::inRandomOrder()->first()?->id ?? Brand::factory(),
            'category_id' => Category::inRandomOrder()->first()?->id ?? Category::factory(),
            'images' => null,
            'is_halal' => $this->faker->boolean(90),
            'is_vegan' => $this->faker->boolean(30),
            'is_active' => true,
            'is_featured' => $this->faker->boolean(20),
        ];
    }

    /**
     * Product that is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    /**
     * Product that is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 0,
        ]);
    }
}

