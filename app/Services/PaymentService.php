<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Debt;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use LaravelIdea\Helper\App\Models\_IH_Payment_QB;

class PaymentService
{
    public function save(Customer $customer, float|int $amount, ?string $transaction_date, ?string $description): Model|Payment|Builder|_IH_Payment_QB
    {
        return Payment::query()->create([
            'business_id' => $customer->business_id,
            'customer_id' => $customer->id,
            'amount' => $amount,
            'paid_at' => $transaction_date,
            'description' => $description,
        ]);
    }

    public function updateDebts(Payment $payment, int $customerId): void
    {
        $remaining = $payment->amount;
        $debts = Debt::where('customer_id', '=', $customerId)
            ->whereIn('status', ['pending', 'partial'])
            ->orderBy('created_at')
            ->get();

        foreach ($debts as $debt) {
            if ($remaining <= 0) {
                break;
            }
            $unpaid = $debt->amount - $debt->paid_amount;
            $apply = min($remaining, $unpaid);
            PaymentAllocation::create([
                'payment_id' => $payment->id,
                'debt_id' => $debt->id,
                'amount_applied' => $apply,
            ]);
            $debt->paid_amount += $apply;
            // Update status
            if ($debt->paid_amount == $debt->amount) {
                $debt->status = 'paid';
            } else {
                $debt->status = 'partial';
            }
            $debt->save();
            $remaining -= $apply;
        }
    }
}
