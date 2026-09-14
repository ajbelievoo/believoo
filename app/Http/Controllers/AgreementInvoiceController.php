<?php

namespace App\Http\Controllers;

use App\Models\AgreementInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class AgreementInvoiceController extends Controller
{
    public function download(AgreementInvoice $invoice)
    {
        // Check if user has access
        if (!Auth::user()->is_admin && $invoice->client_id !== Auth::id()) {
            abort(403);
        }

        $pdf = PDF::loadView('invoices.pdf', compact('invoice'));

        return $pdf->download('Invoice-' . $invoice->invoice_number . '.pdf');
    }

    public function view(AgreementInvoice $invoice)
    {
        // Check if user has access
        if (!Auth::user()->is_admin && $invoice->client_id !== Auth::id()) {
            abort(403);
        }

        return view('invoices.view', compact('invoice'));
    }
}
