<?php

namespace App\Http\Controllers;

use App\Models\Agreement;
use App\Models\AgreementHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class AgreementController extends Controller
{
    public function view(Agreement $agreement)
    {
        // Ensure user has access
        if ($agreement->client_id !== Auth::id()) {
            abort(403);
        }

        // Load relationships
        $agreement->load(['workItems', 'milestones', 'histories']);

        // Update status to viewed if it was just sent
        if ($agreement->status === 'sent') {
            $agreement->update(['status' => 'viewed']);
            
            AgreementHistory::create([
                'agreement_id' => $agreement->id,
                'user_id' => Auth::id(),
                'action' => 'viewed',
                'description' => 'Client viewed the agreement',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }

        return view('agreements.view', compact('agreement'));
    }

    public function download(Agreement $agreement)
    {
        // Ensure user has access
        if ($agreement->client_id !== Auth::id()) {
            abort(403);
        }

        $agreement->load(['workItems', 'milestones']);

        // Generate PDF
        $pdf = Pdf::loadView('agreements.pdf', compact('agreement'));

        $filename = 'Agreement-' . $agreement->agreement_number . '.pdf';

        return $pdf->download($filename);
    }
}
