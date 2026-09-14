<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CallRequest;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index()
    {
        $leads = CallRequest::latest()->paginate(20);
        return view('admin.leads.index', compact('leads'));
    }

    public function show(CallRequest $lead)
    {
        return view('admin.leads.show', compact('lead'));
    }

    public function updateStatus(Request $request, CallRequest $lead)
    {
        $validated = $request->validate([
            'status' => 'required|in:new,contacted,qualified,converted,lost',
        ]);

        $lead->update($validated);
        return redirect()->back()->with('success', 'Lead status updated');
    }
}
