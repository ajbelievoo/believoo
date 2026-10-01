<?php

namespace App\Mail;

use App\Models\Bconnect\Invoice;
use App\Services\BconnectInvoicePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\View;

class BconnectInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public string $type = 'created' // created | paid | reminder
    ) {}

    public function envelope(): Envelope
    {
        $subject = match($this->type) {
            'paid' => 'Payment received: Invoice #' . $this->invoice->invoice_number,
            'reminder' => 'Invoice #' . $this->invoice->invoice_number . ' is due',
            default => 'Invoice #' . $this->invoice->invoice_number . ' from Bmydesk by Believoo',
        };

        return new Envelope(
            subject: $subject,
            from: new \Illuminate\Mail\Mailables\Address('bconnect@believoo.com', 'Bmydesk by Believoo'),
            replyTo: [new \Illuminate\Mail\Mailables\Address('support@believoo.com', 'Believoo Support')],
        );
    }

    public function content(): Content
    {
        $view = match($this->type) {
            'paid' => 'emails.bconnect-invoice-paid',
            'reminder' => 'emails.bconnect-invoice-reminder',
            default => 'emails.bconnect-invoice',
        };

        $html = View::make($view, [
            'invoice' => $this->invoice,
            'company' => $this->invoice->company,
            'payUrl' => route('bconnect.billing.pay', $this->invoice->id),
            'bconnectBrand' => \App\Helpers\BconnectHelper::brandData(),
        ])->render();

        return new Content(htmlString: $html);
    }

    public function attachments(): array
    {
        try {
            $pdf = BconnectInvoicePdfService::generate($this->invoice);
            return [
                \Illuminate\Mail\Mailables\Attachment::fromData(fn() => $pdf, 'invoice-' . $this->invoice->invoice_number . '.pdf')
                    ->withMime('application/pdf'),
            ];
        } catch (\Throwable $e) {
            \Log::error('Bmydesk invoice PDF attachment failed: ' . $e->getMessage());
            return [];
        }
    }
}
