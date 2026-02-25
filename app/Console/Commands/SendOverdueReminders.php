<?php

namespace App\Console\Commands;

use App\Jobs\DebtReminderJob;
use App\Models\Debt;
use App\Models\ReminderLog;
use Illuminate\Console\Command;

class SendOverdueReminders extends Command
{
    protected $signature = 'reminders:send';

    protected $description = 'Send overdue debt reminders to customers';

    public function handle(): void
    {
        $this->info('Checking for overdue debts...');

        // Get all overdue debts (unpaid or partially paid)
        $overdueDebts = Debt::query()
            ->whereDate('due_date', '<', now())
            ->whereColumn('amount', '>', 'paid_amount')
            ->with(['customer'])
            ->get();

        if ($overdueDebts->isEmpty()) {
            $this->info('No overdue debts found.');

            return;
        }

        // Group debts per customer
        $grouped = $overdueDebts->groupBy('customer_id');

        foreach ($grouped as $customerId => $debts) {
            $customer = $debts->first()->customer;
            if (! $customer || ! $customer->email) {
                $this->warn("Customer {$customerId} has no email. Skipping.");

                continue;
            }
            // Calculate total overdue
            $totalOverdue = $debts->sum(function ($debt) {
                return $debt->amount - $debt->paid_amount;
            });

            // Avoid duplicate reminders same day
            $alreadySentToday = ReminderLog::where('customer_id', '=', $customer->id)
                ->whereDate('sent_at', today())
                ->exists();

            if ($alreadySentToday) {
                $this->info("Reminder already sent today for {$customer->name}");

                continue;
            }

            DebtReminderJob::dispatch($customer, $totalOverdue, $debts);
        }

        $this->info('Reminder process completed.');
    }
}
