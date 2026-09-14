<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoicePaidNotification extends Notification
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
            ->subject('Payment Received - Invoice #' . $this->invoice->invoice_number)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Thank you for your payment!')
            ->line('')
            ->line('Payment Details:')
            ->line('Invoice Number: ' . $this->invoice->invoice_number)
            ->line('Amount Paid: $' . number_format($this->invoice->total_amount, 2))
            ->line('Payment Date: ' . $this->invoice->paid_at->format('M d, Y'))
            ->line('Payment Method: ' . ucfirst($this->invoice->payment_method))
            ->line('')
            ->line('Your project will continue as scheduled.')
            ->action('View Invoice', route('client.dashboard', ['tab' => 'invoices']))
            ->salutation('Best regards, The Believoo Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'amount' => $this->invoice->total_amount,
            'type' => 'invoice_paid',
            'action_url' => route('client.dashboard', ['tab' => 'invoices']),
        ];
    }
}
