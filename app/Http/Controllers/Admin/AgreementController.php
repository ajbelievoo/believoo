<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agreement;
use App\Models\AgreementHistory;
use App\Models\AgreementMilestone;
use App\Models\AgreementRequest;
use App\Models\AgreementWorkItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgreementController extends Controller
{
    public function index()
    {
        $agreements = Agreement::with(['client'])->latest()->paginate(20);
        return view('admin.agreements.index', compact('agreements'));
    }

    public function create()
    {
        $clients = User::where('is_admin', false)->orderBy('name')->get();
        return view('admin.agreements.create', compact('clients'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'client_id' => 'required|exists:users,id',
            'client_name' => 'required|string|max:255',
            'service_provider_name' => 'required|string|max:255',
            'lead_developer' => 'nullable|string|max:255',
            'project_name' => 'required|string|max:255',
            'project_overview' => 'nullable|string',
            'technical_specs' => 'nullable|string',
            'total_amount' => 'required|numeric|min:0',
            'currency' => 'required|string|max:10',
            'upfront_amount' => 'nullable|numeric|min:0',
            'timeline_months' => 'required|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'payment_terms' => 'nullable|string',
            'deliverables' => 'nullable|string',
            'support_terms' => 'nullable|string',
            'additional_terms' => 'nullable|string',
            'status' => 'required|in:draft,sent,viewed,signed,cancelled',
            'work_items' => 'nullable|array',
            'work_items.*.item_name' => 'required_with:work_items|string|max:255',
            'work_items.*.description' => 'nullable|string',
            'work_items.*.amount' => 'nullable|numeric|min:0',
            'work_items.*.timeline_days' => 'nullable|integer|min:1',
            'milestones' => 'nullable|array',
            'milestones.*.phase_name' => 'required_with:milestones|string|max:255',
            'milestones.*.description' => 'nullable|string',
            'milestones.*.payment_amount' => 'required_with:milestones|numeric|min:0',
            'milestones.*.timeline_month' => 'nullable|integer|min:1',
            'milestones.*.due_date' => 'nullable|date',
            'milestones.*.status' => 'nullable|in:pending,completed,paid',
        ]);

        DB::beginTransaction();

        try {
            $agreement = Agreement::create($validated);

            if (!empty($validated['work_items'])) {
                foreach ($validated['work_items'] as $index => $item) {
                    AgreementWorkItem::create([
                        'agreement_id' => $agreement->id,
                        'item_name' => $item['item_name'],
                        'description' => $item['description'] ?? null,
                        'amount' => $item['amount'] ?? 0,
                        'timeline_days' => $item['timeline_days'] ?? null,
                        'sort_order' => $index,
                    ]);
                }
            }

            if (!empty($validated['milestones'])) {
                foreach ($validated['milestones'] as $index => $milestone) {
                    AgreementMilestone::create([
                        'agreement_id' => $agreement->id,
                        'phase_name' => $milestone['phase_name'],
                        'description' => $milestone['description'] ?? null,
                        'payment_amount' => $milestone['payment_amount'],
                        'timeline_month' => $milestone['timeline_month'] ?? null,
                        'due_date' => $milestone['due_date'] ?? null,
                        'status' => $milestone['status'] ?? 'pending',
                        'sort_order' => $index,
                    ]);
                }
            }

            AgreementHistory::create([
                'agreement_id' => $agreement->id,
                'user_id' => auth()->id(),
                'action' => 'created',
                'description' => 'Agreement created from admin panel',
            ]);

            DB::commit();

            return redirect()->route('admin.agreements.index')->with('success', 'Agreement created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Failed to create agreement: ' . $e->getMessage());
        }
    }

    public function show(Agreement $agreement)
    {
        $agreement->load(['client', 'invoices', 'milestones']);
        return view('admin.agreements.show', compact('agreement'));
    }

    public function updateStatus(Request $request, Agreement $agreement)
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,sent,viewed,signed,cancelled',
        ]);

        $agreement->update($validated);
        return redirect()->back()->with('success', 'Agreement status updated');
    }

    // --- Agreement Requests (Project Requests from Clients) ---

    public function requests()
    {
        $requests = AgreementRequest::with(['client', 'agreement'])
            ->latest()
            ->paginate(20);
        return view('admin.agreements.requests.index', compact('requests'));
    }

    public function showRequest(AgreementRequest $agreementRequest)
    {
        $agreementRequest->load(['client', 'agreement']);
        return view('admin.agreements.requests.show', compact('agreementRequest'));
    }

    public function updateRequestStatus(Request $request, AgreementRequest $agreementRequest)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,under_review,approved,rejected,converted',
        ]);

        $data = ['status' => $validated['status']];

        if ($validated['status'] === 'under_review' || $validated['status'] === 'approved' || $validated['status'] === 'rejected') {
            $data['reviewed_at'] = now();
        }
        if ($validated['status'] === 'converted') {
            $data['converted_at'] = now();
        }

        $agreementRequest->update($data);

        return redirect()->back()->with('success', 'Request status updated to ' . ucfirst(str_replace('_', ' ', $validated['status'])));
    }
}
