<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 100000, 5000000);
        $discountPercent = $this->faker->randomElement([0, 5, 10, 15, 20]);
        $discountAmount = $subtotal * ($discountPercent / 100);
        $taxRate = 11.00;
        $subtotalBeforeTax = $subtotal - $discountAmount;
        $taxAmount = $subtotalBeforeTax * ($taxRate / 100);
        $shippingCost = $this->faker->randomElement([0, 25000, 50000, 75000]);
        $totalAmount = $subtotalBeforeTax + $taxAmount + $shippingCost;

        return [
            'user_id' => User::factory(),
            'order_number' => 'ORD-' . strtoupper($this->faker->unique()->bothify('????####')),
            'status' => $this->faker->randomElement([
                Order::STATUS_PENDING,
                Order::STATUS_PENDING_PAYMENT,
                Order::STATUS_PROCESSING,
                Order::STATUS_SHIPPED,
                Order::STATUS_DELIVERED,
            ]),
            'payment_status' => $this->faker->randomElement([
                Order::PAYMENT_PENDING,
                Order::PAYMENT_PAID,
            ]),
            'payment_method' => $this->faker->randomElement(['bank_transfer', 'whatsapp', 'term']),
            'shipping_address' => $this->faker->address(),
            'shipping_method' => $this->faker->randomElement(['standard', 'express', 'pickup']),
            'shipping_cost' => $shippingCost,
            'notes' => $this->faker->optional()->sentence(),
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'discount_percent' => $discountPercent,
            'subtotal_before_tax' => $subtotalBeforeTax,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'is_tax_inclusive' => false,
            'total_amount' => $totalAmount,
            'amount_paid' => 0,
            'balance_due' => $totalAmount,
            'payment_term_days' => $this->faker->randomElement([0, 7, 14, 30]),
        ];
    }

    /**
     * Order in pending status.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_PENDING,
            'payment_status' => Order::PAYMENT_PENDING,
        ]);
    }

    /**
     * Order in processing status.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_PROCESSING,
            'payment_status' => Order::PAYMENT_PAID,
            'amount_paid' => $attributes['total_amount'],
            'balance_due' => 0,
        ]);
    }

    /**
     * Order that is paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => Order::PAYMENT_PAID,
            'amount_paid' => $attributes['total_amount'],
            'balance_due' => 0,
            'last_payment_date' => now(),
        ]);
    }

    /**
     * Order that is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_CANCELLED,
        ]);
    }

    /**
     * Order that is delivered.
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_DELIVERED,
            'payment_status' => Order::PAYMENT_PAID,
            'amount_paid' => $attributes['total_amount'],
            'balance_due' => 0,
        ]);
    }

    /**
     * Order with payment due date.
     */
    public function withPaymentDue(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_term_days' => 30,
            'payment_due_date' => now()->addDays(30),
        ]);
    }
}
