<?php

namespace App\Jobs;

use App\Mail\DebtReminderMail;
use App\Models\Customer;
use App\Models\ReminderLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class DebtReminderJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Customer $customer, protected $totalOverdue, protected $debts)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Send email
            $customer = $this->customer;
            $totalOverdue = $this->totalOverdue;
            Mail::to($customer->email)->send(new DebtReminderMail($customer, $totalOverdue, $this->debts));
            // Log reminder
            ReminderLog::create([
                'customer_id' => $customer->id,
                'total_amount' => $totalOverdue,
                'method' => 'email',
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            info("Reminder sent to {$customer->name}");

        } catch (\Exception $e) {

            ReminderLog::create([
                'customer_id' => $customer->id,
                'total_amount' => $totalOverdue,
                'method' => 'email',
                'status' => 'failed',
                'sent_at' => now(),
            ]);
            info("Failed sending to {$customer->name}: " . $e->getMessage());
        }
    }
}
