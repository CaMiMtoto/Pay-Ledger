<?php

namespace Database\Factories;

use App\Constants\Status;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Debt;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class DebtFactory extends Factory
{
    protected $model = Debt::class;

    public function definition(): array
    {
        return [
            'description' => $this->faker->text(),
            'due_date' => $this->faker->dateTimeBetween('-1 month', '+1 month'),
            'amount' => $this->faker->randomFloat(2, 100, 1000),
            'paid_amount' => 0,
            'business_id' => Business::factory(),
            'customer_id' => Customer::factory(),
        ];
    }
}
