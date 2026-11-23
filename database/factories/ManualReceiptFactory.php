<?php

namespace Database\Factories;

use App\Models\ManualReceipt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Pass `branch_id` and `client_id` — branches and clients have no factories.
 *
 * @extends Factory<ManualReceipt>
 */
class ManualReceiptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'amount' => fake()->randomFloat(2, 50, 5000),
            'currency' => 'USD',
            'payment_method' => fake()->randomElement(ManualReceipt::PAYMENT_METHODS),
            'paid_on' => fake()->dateTimeBetween('2019-01-01', '2024-12-31'),
            'description' => 'Shipping charges',
            'related_reference' => null,
            'transaction_reference' => null,
            'received_by' => null,
            'notes' => null,
        ];
    }

    public function inCedis(): static
    {
        return $this->state(['currency' => 'GHS']);
    }
}
