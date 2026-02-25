<?php

namespace Database\Factories;

use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class BusinessFactory extends Factory
{
    protected $model = Business::class;

    public function definition(): array
    {
        $subscription_statuses = ['active', 'inactive', 'pending', 'cancelled', 'trial'];

        return [
            'name' => $this->faker->company(),
            'phone' => $this->faker->phoneNumber(),
            'subscription_status' => $this->faker->randomElement($subscription_statuses),
            'address' => $this->faker->address(),
            'email' => $this->faker->unique()->safeEmail(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
