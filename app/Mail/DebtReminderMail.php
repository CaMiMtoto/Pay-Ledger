<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DebtReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $customer;

    public $totalOverdue;

    public $debts;

    /**
     * Create a new message instance.
     */
    public function __construct($customer, $totalOverdue, $debts)
    {
        $this->customer = $customer;
        $this->totalOverdue = $totalOverdue;
        $this->debts = $debts;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Debt Reminder',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.debt-reminder',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
