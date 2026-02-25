<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Debt;

class DebtService
{
    /**
     * @return Debt
     */
    public function save(Customer $customer, float|int $amount, ?string $due_date, ?string $description): Debt
    {
        return Debt::create([
            'business_id' => $customer->business_id,
            'customer_id' => $customer->id,
            'amount' => $amount,
            'due_date' => $due_date,
            'description' => $description,
        ]);
    }
}
