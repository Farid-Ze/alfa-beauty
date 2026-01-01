<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 10);
        $unitPrice = $this->faker->randomFloat(2, 25000, 500000);
        $taxRate = 11.00;
        $unitPriceBeforeTax = $unitPrice / (1 + $taxRate / 100);
        $subtotalBeforeTax = $unitPriceBeforeTax * $quantity;
        $taxAmount = $subtotalBeforeTax * ($taxRate / 100);
        $totalPrice = $unitPrice * $quantity;

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'unit_price_before_tax' => $unitPriceBeforeTax,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'subtotal_before_tax' => $subtotalBeforeTax,
            'batch_allocations' => null,
        ];
    }

    /**
     * Item with batch allocations.
     */
    public function withBatch(): static
    {
        return $this->state(fn (array $attributes) => [
            'batch_allocations' => [
                [
                    'batch_id' => 1,
                    'batch_number' => 'B' . $this->faker->numerify('####'),
                    'quantity' => $attributes['quantity'],
                    'expires_at' => now()->addMonths(6)->toDateString(),
                ],
            ],
        ]);
    }
}
