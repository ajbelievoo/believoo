<?php

namespace App\Services;

use App\Models\Bconnect\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;

class BconnectInvoicePdfService
{
    public static function generate(Invoice $invoice): string
    {
        $company = $invoice->company;
        $client = $invoice->client;
        $brand = \App\Helpers\BconnectHelper::brandData();

        $pdf = Pdf::loadView('bconnect.invoices.pdf', [
            'invoice' => $invoice,
            'company' => $company,
            'client' => $client,
            'brand' => $brand,
        ]);

        $pdf->setPaper('a4');

        return $pdf->output();
    }

    public static function save(Invoice $invoice): string
    {
        $output = self::generate($invoice);
        $filename = 'bconnect/invoices/' . $invoice->invoice_number . '.pdf';
        \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $output);
        return $filename;
    }
}
