<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Brand>
 */
class BrandFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company(),
            'slug' => $this->faker->unique()->slug(),
            'logo_url' => null,
            'description' => $this->faker->optional()->sentence(),
            'origin_country' => $this->faker->optional()->country(),
            'is_own_brand' => false,
            'is_featured' => false,
            'sort_order' => 0,
        ];
    }
}
