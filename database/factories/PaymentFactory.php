<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'customer_id' => Customer::factory(),
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'paid_at' => $this->faker->dateTimeThisMonth(),
            'description' => $this->faker->sentence(),
        ];
    }
}
