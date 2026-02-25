<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Transaction;

class TransactionService
{
    /**
     * @param Customer $customer
     * @param float|int $amount
     * @param int $direction
     * @param string|null $transaction_date
     * @param string|null $description
     * @return Transaction
     */
    public function save(Customer $customer, float|int $amount, int $direction, ?string $transaction_date, ?string $description): Transaction
    {
        return Transaction::create([
            'business_id' => $customer->business_id,
            'customer_id' => $customer->id,
            'amount' => $amount,
            'direction' => $direction, // 1 for debt -1 for payment
            'transaction_date' => $transaction_date,
            'description' => $description,
            'created_by' => auth()->id(),
        ]);
    }
}
