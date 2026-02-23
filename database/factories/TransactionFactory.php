<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::inRandomOrder()->first()->id,
            'direction' => $this->faker->numberBetween(-1,1),
            'amount'=>$this->faker->numberBetween(1000, 100000),
            'reference' => $this->faker->isbn13(),
            'description' => $this->faker->text(),
            'transaction_date' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'created_by' => User::inRandomOrder()->first()->id,
            'created_at' => $this->faker->dateTimeBetween('-2 months', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-2 months', 'now'),
            'customer_id' => Customer::inRandomOrder()->first()->id,
        ];
    }
}
