<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PointTransaction>
 */
class PointTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['earn', 'redeem', 'adjust', 'purchase', 'bonus', 'review']);
        $amount = match ($type) {
            'earn', 'bonus', 'review' => $this->faker->numberBetween(10, 500),
            'redeem', 'purchase' => -$this->faker->numberBetween(10, 500),
            'adjust' => $this->faker->numberBetween(-100, 100),
        };

        return [
            'user_id' => User::factory(),
            'order_id' => null,
            'amount' => $amount,
            'type' => $type,
            'description' => $this->generateDescription($type, abs($amount)),
            'balance_after' => $this->faker->numberBetween(0, 5000),
        ];
    }

    /**
     * Generate description based on transaction type.
     */
    private function generateDescription(string $type, int $amount): string
    {
        return match ($type) {
            'earn' => "Earned {$amount} points from order",
            'redeem' => "Redeemed {$amount} points",
            'adjust' => 'Points adjustment by admin',
            'purchase' => "Used {$amount} points for purchase discount",
            'bonus' => "Bonus points: Loyalty tier upgrade",
            'review' => "Earned {$amount} points for product review",
            default => 'Point transaction',
        };
    }

    /**
     * Transaction from order purchase (earning points).
     */
    public function fromOrder(?Order $order = null): static
    {
        return $this->state(function (array $attributes) use ($order) {
            $orderId = $order?->id ?? Order::factory();
            $amount = $order ? (int) floor($order->total_amount / 10000) : $this->faker->numberBetween(10, 500);
            
            return [
                'order_id' => $orderId,
                'type' => 'earn',
                'amount' => $amount,
                'description' => "Earned {$amount} points from order",
            ];
        });
    }

    /**
     * Transaction for redeeming points.
     */
    public function redeem(?int $amount = null): static
    {
        $points = $amount ?? $this->faker->numberBetween(50, 500);
        
        return $this->state(fn (array $attributes) => [
            'type' => 'redeem',
            'amount' => -$points,
            'description' => "Redeemed {$points} points for discount",
        ]);
    }

    /**
     * Transaction for bonus points.
     */
    public function bonus(string $reason = 'Tier upgrade bonus'): static
    {
        $points = $this->faker->numberBetween(100, 1000);
        
        return $this->state(fn (array $attributes) => [
            'type' => 'bonus',
            'amount' => $points,
            'description' => "Bonus points: {$reason}",
        ]);
    }

    /**
     * Transaction for admin adjustment.
     */
    public function adjustment(?int $amount = null): static
    {
        $points = $amount ?? $this->faker->numberBetween(-100, 100);
        
        return $this->state(fn (array $attributes) => [
            'type' => 'adjust',
            'amount' => $points,
            'description' => 'Points adjustment by admin',
        ]);
    }

    /**
     * Transaction for product review.
     */
    public function review(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'review',
            'amount' => 50,
            'description' => 'Earned 50 points for product review',
        ]);
    }
}
