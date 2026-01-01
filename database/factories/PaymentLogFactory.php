<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\PaymentLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentLog>
 */
class PaymentLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'payment_method' => $this->faker->randomElement([
                PaymentLog::METHOD_WHATSAPP,
                PaymentLog::METHOD_MANUAL_TRANSFER,
                PaymentLog::METHOD_BANK_TRANSFER,
            ]),
            'provider' => $this->faker->randomElement(['BCA', 'Mandiri', 'BNI', 'BRI', 'CIMB']),
            'amount' => $this->faker->randomFloat(2, 100000, 5000000),
            'currency' => 'IDR',
            'status' => PaymentLog::STATUS_PENDING,
            'reference_number' => strtoupper($this->faker->bothify('PAY-????-####')),
            'external_id' => null,
            'notes' => null,
            'metadata' => null,
        ];
    }

    /**
     * Payment that is confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentLog::STATUS_CONFIRMED,
            'confirmed_by' => User::factory(),
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Payment that is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentLog::STATUS_PENDING,
            'confirmed_by' => null,
            'confirmed_at' => null,
        ]);
    }

    /**
     * Payment that failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentLog::STATUS_FAILED,
            'notes' => 'Payment failed: ' . $this->faker->sentence(),
        ]);
    }

    /**
     * Payment via WhatsApp.
     */
    public function whatsapp(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => PaymentLog::METHOD_WHATSAPP,
        ]);
    }

    /**
     * Payment via bank transfer.
     */
    public function bankTransfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => PaymentLog::METHOD_BANK_TRANSFER,
            'external_id' => strtoupper($this->faker->bothify('TRX-############')),
        ]);
    }
}
