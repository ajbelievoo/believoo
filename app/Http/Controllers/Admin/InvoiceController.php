<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with(['user', 'agreement'])->latest()->paginate(20);
        return view('admin.invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['user', 'agreement', 'items']);
        return view('admin.invoices.show', compact('invoice'));
    }

    public function updateStatus(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,paid,overdue,cancelled',
        ]);

        $invoice->update($validated);
        return redirect()->back()->with('success', 'Invoice status updated');
    }
}
