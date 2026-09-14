<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class InvoicePdfService
{
    /**
     * Generate PDF for an invoice
     */
    public function generateInvoicePdf(Invoice $invoice): ?string
    {
        try {
            $user = $invoice->user;
            $settings = $this->getCompanySettings();

            $data = [
                'invoice' => $invoice,
                'user' => $user,
                'settings' => $settings,
                'invoice_date' => Carbon::parse($invoice->invoice_date)->format('F d, Y'),
                'due_date' => Carbon::parse($invoice->due_date)->format('F d, Y'),
                'paid_date' => $invoice->paid_date ? Carbon::parse($invoice->paid_date)->format('F d, Y') : null,
            ];

            $pdf = PDF::loadView('invoices.vps', $data);
            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

            $filename = 'invoices/' . $invoice->invoice_number . '.pdf';
            $pdfContent = $pdf->output();

            // Save to storage
            Storage::disk('public')->put($filename, $pdfContent);

            // Update invoice with PDF path
            $invoice->update(['pdf_path' => $filename]);

            Log::info("Invoice PDF generated: {$filename}");

            return Storage::disk('public')->path($filename);
        } catch (\Exception $e) {
            Log::error("Failed to generate invoice PDF", [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Stream PDF for download/view
     */
    public function streamInvoicePdf(Invoice $invoice)
    {
        try {
            // If PDF already exists, serve it
            if ($invoice->pdf_path && Storage::disk('public')->exists($invoice->pdf_path)) {
                return Storage::disk('public')->download($invoice->pdf_path, "Invoice-{$invoice->invoice_number}.pdf");
            }

            // Otherwise generate new PDF
            $user = $invoice->user;
            $settings = $this->getCompanySettings();

            $data = [
                'invoice' => $invoice,
                'user' => $user,
                'settings' => $settings,
                'invoice_date' => Carbon::parse($invoice->invoice_date)->format('F d, Y'),
                'due_date' => Carbon::parse($invoice->due_date)->format('F d, Y'),
                'paid_date' => $invoice->paid_date ? Carbon::parse($invoice->paid_date)->format('F d, Y') : null,
            ];

            $pdf = PDF::loadView('invoices.vps', $data);
            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

            return $pdf->download("Invoice-{$invoice->invoice_number}.pdf");
        } catch (\Exception $e) {
            Log::error("Failed to stream invoice PDF", [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get company settings for invoice branding
     */
    protected function getCompanySettings(): array
    {
        return [
            'company_name' => config('app.name', 'Believoo'),
            'company_address' => setting('company_address', 'Your Company Address'),
            'company_phone' => setting('company_phone', '+91-XXXXXXXXXX'),
            'company_email' => setting('company_email', 'billing@believoo.com'),
            'company_website' => config('app.url', 'https://believoo.com'),
            'gst_number' => setting('gst_number', 'GSTINXXXXXXX'),
            'logo_url' => asset('storage/' . setting('site_logo', 'logo.png')),
            'currency_symbol' => setting('currency_symbol', '₹'),
        ];
    }
}
