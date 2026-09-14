<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentProofSubmittedNotification extends Notification
{
    use Queueable;

    protected $paymentProof;

    public function __construct($paymentProof)
    {
        $this->paymentProof = $paymentProof;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🧾 Payment Proof Submitted - ' . $this->paymentProof->agreement->project_name)
            ->greeting('Hello Admin,')
            ->line('A client has submitted payment proof for a milestone.')
            ->line('Project: ' . $this->paymentProof->agreement->project_name)
            ->line('Client: ' . $this->paymentProof->user->name)
            ->line('Milestone: ' . $this->paymentProof->milestone->phase_name)
            ->line('Amount: ' . number_format($this->paymentProof->amount, 2))
            ->line('Payment Method: ' . $this->paymentProof->payment_method_label)
            ->line('Transaction ID: ' . $this->paymentProof->transaction_id)
            ->action('Verify Payment', route('filament.admin.resources.agreements.view', $this->paymentProof->agreement))
            ->line('Please verify the payment and update the milestone status.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'payment_proof_id' => $this->paymentProof->id,
            'agreement_id' => $this->paymentProof->agreement_id,
            'project_name' => $this->paymentProof->agreement->project_name,
            'client_name' => $this->paymentProof->user->name,
            'amount' => $this->paymentProof->amount,
            'payment_method' => $this->paymentProof->payment_method,
            'type' => 'payment_proof_submitted',
            'action_url' => route('filament.admin.resources.agreements.view', $this->paymentProof->agreement),
        ];
    }
}
