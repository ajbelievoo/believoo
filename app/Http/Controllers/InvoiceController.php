<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\InvoicePdfService;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    protected InvoicePdfService $pdfService;

    public function __construct(InvoicePdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Download invoice as PDF
     */
    public function download(Invoice $invoice)
    {
        // Check authorization
        if (!Auth::check()) {
            abort(403, 'Unauthorized');
        }

        $user = Auth::user();
        
        // Allow if user owns the invoice or is admin
        if ($invoice->user_id !== $user->id && !$user->is_admin) {
            abort(403, 'Unauthorized');
        }

        // Use the PDF service to generate and download
        $response = $this->pdfService->streamInvoicePdf($invoice);
        
        if ($response) {
            return $response;
        }

        // Fallback to HTML if PDF generation fails
        $html = $this->generateInvoiceHtml($invoice);
        return response($html, 200, [
            'Content-Type' => 'text/html',
            'Content-Disposition' => 'attachment; filename="invoice-' . $invoice->invoice_number . '.html"',
        ]);
    }

    /**
     * View invoice
     */
    public function view(Invoice $invoice)
    {
        if (!Auth::check()) {
            abort(403, 'Unauthorized');
        }

        $user = Auth::user();
        
        if ($invoice->user_id !== $user->id && !$user->is_admin) {
            abort(403, 'Unauthorized');
        }

        return view('invoices.view', compact('invoice'));
    }

    /**
     * Generate invoice HTML
     */
    private function generateInvoiceHtml(Invoice $invoice): string
    {
        $lineItems = $invoice->line_items ?? [];
        $billingDetails = $invoice->billing_details ?? [];
        
        $itemsHtml = '';
        foreach ($lineItems as $item) {
            $itemsHtml .= "
                <tr>
                    <td>" . htmlspecialchars($item['item'] ?? 'Service') . "</td>
                    <td>" . htmlspecialchars($item['description'] ?? '') . "</td>
                    <td>" . ($item['quantity'] ?? 1) . "</td>
                    <td>₹" . number_format($item['price'] ?? 0, 2) . "</td>
                    <td>₹" . number_format($item['total'] ?? ($item['price'] ?? 0), 2) . "</td>
                </tr>
            ";
        }

        $user = $invoice->user;
        $statusLabel = ucfirst($invoice->status);
        $paidDate = $invoice->paid_at ? $invoice->paid_at->format('d M Y') : 'N/A';
        $dueDate = $invoice->due_date ? $invoice->due_date->format('d M Y') : 'N/A';
        $invoiceDate = $invoice->created_at->format('d M Y');
        
        return "<!DOCTYPE html>
<html>
<head>
    <title>Invoice {$invoice->invoice_number}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 30px; }
        .invoice-title { font-size: 24px; font-weight: bold; color: #333; }
        .invoice-number { font-size: 14px; color: #666; margin-top: 10px; }
        .section { margin-bottom: 25px; }
        .section-title { font-weight: bold; font-size: 14px; color: #333; margin-bottom: 10px; }
        .details { display: flex; justify-content: space-between; }
        .details-left, .details-right { width: 48%; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #f5f5f5; padding: 10px; text-align: left; border: 1px solid #ddd; }
        td { padding: 10px; border: 1px solid #ddd; }
        .totals { text-align: right; margin-top: 20px; }
        .total-row { margin: 5px 0; }
        .grand-total { font-weight: bold; font-size: 18px; margin-top: 15px; padding-top: 15px; border-top: 2px solid #333; }
        .status { display: inline-block; padding: 5px 15px; border-radius: 3px; font-weight: bold; text-transform: uppercase; font-size: 12px; }
        .status-paid { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .status-refunded { background: #d1ecf1; color: #0c5460; }
        .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #ddd; padding-top: 20px; }
    </style>
</head>
<body>
    <div class='header'>
        <div class='invoice-title'>INVOICE</div>
        <div class='invoice-number'>{$invoice->invoice_number}</div>
        <div style='margin-top: 15px;'>
            <span class='status status-{$invoice->status}'>{$statusLabel}</span>
        </div>
    </div>
    
    <div class='section details'>
        <div class='details-left'>
            <div class='section-title'>Bill To:</div>
            <div><strong>" . htmlspecialchars($user->name) . "</strong></div>
            <div>" . htmlspecialchars($user->email) . "</div>
            " . ($user->phone ? '<div>' . htmlspecialchars($user->phone) . '</div>' : '') . "
        </div>
        <div class='details-right'>
            <div class='section-title'>Invoice Details:</div>
            <div><strong>Invoice Date:</strong> {$invoiceDate}</div>
            <div><strong>Due Date:</strong> {$dueDate}</div>
            <div><strong>Paid Date:</strong> {$paidDate}</div>
            <div><strong>Payment Method:</strong> " . ucfirst($invoice->payment_gateway ?? 'N/A') . "</div>
        </div>
    </div>
    
    <div class='section'>
        <div class='section-title'>Description:</div>
        <div>" . htmlspecialchars($invoice->description) . "</div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Description</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            {$itemsHtml}
            " . (empty($lineItems) ? "<tr><td colspan='5'>" . htmlspecialchars($invoice->description) . "</td></tr>" : '') . "
        </tbody>
    </table>
    
    <div class='totals'>
        <div class='total-row'><strong>Subtotal:</strong> ₹" . number_format($invoice->amount, 2) . "</div>
        <div class='total-row'><strong>Tax:</strong> ₹" . number_format($invoice->tax_amount, 2) . "</div>
        <div class='grand-total'><strong>Total Amount:</strong> ₹" . number_format($invoice->total_amount, 2) . "</div>
    </div>
    
    " . ($invoice->notes ? "<div class='section'><div class='section-title'>Notes:</div><div>" . htmlspecialchars($invoice->notes) . "</div></div>" : '') . "
    
    <div class='footer'>
        <p>Thank you for your business!</p>
        <p>This is a computer-generated invoice and does not require a signature.</p>
    </div>
</body>
</html>";
    }
}
