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
            'due_date' => $this->faker->dateTimeBetween('+1 week', '+1 month'),
            'status' => $this->faker->randomElement([Status::UNPAID, Status::PAID, Status::PARTIAL]),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'amount' => $this->faker->randomFloat(),
            'remaining_amount' => $this->faker->randomFloat(),
            'business_id' => Business::query()->inRandomOrder()->first()->id,
            'customer_id' => Customer::query()->inRandomOrder()->first()->id,
        ];
    }
}
