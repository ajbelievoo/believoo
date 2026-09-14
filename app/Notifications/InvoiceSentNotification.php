<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceSentNotification extends Notification
{
    use Queueable;

    protected $invoice;

    public function __construct($invoice)
    {
        $this->invoice = $invoice;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Invoice #' . $this->invoice->invoice_number . ' from Believoo')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Please find attached your invoice for the project: ' . $this->invoice->agreement->project_name)
            ->line('')
            ->line('Invoice Details:')
            ->line('Invoice Number: ' . $this->invoice->invoice_number)
            ->line('Amount Due: $' . number_format($this->invoice->total_amount, 2))
            ->line('Due Date: ' . $this->invoice->due_date->format('M d, Y'))
            ->line('')
            ->action('View Invoice', route('client.dashboard', ['tab' => 'invoices']))
            ->line('Thank you for your business!')
            ->salutation('Best regards, The Believoo Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'amount' => $this->invoice->total_amount,
            'type' => 'invoice_sent',
            'action_url' => route('client.dashboard', ['tab' => 'invoices']),
        ];
    }
}
