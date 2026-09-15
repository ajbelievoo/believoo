<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendCampaignEmails;
use App\Models\EmailCampaign;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Http\Request;

class EmailCampaignController extends Controller
{
    public function index(Request $request)
    {
        $query = EmailCampaign::latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($sq) use ($q) {
                $sq->where('name', 'like', "%{$q}%")
                   ->orWhere('subject', 'like', "%{$q}%");
            });
        }

        $campaigns = $query->paginate(25)->withQueryString();
        $statuses = ['draft', 'scheduled', 'sending', 'sent', 'paused'];

        return view('admin.campaigns.index', compact('campaigns', 'statuses'));
    }

    public function create()
    {
        $segments = $this->segments();
        return view('admin.campaigns.create', compact('segments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'from_name' => 'nullable|string|max:255',
            'from_email' => 'nullable|email|max:255',
            'test_email' => 'nullable|email|max:255',
            'content_html' => 'required|string',
            'segment' => 'required|string|in:' . implode(',', array_keys($this->segments())),
            'scheduled_at' => 'nullable|date',
        ]);

        $validated['content_text'] = strip_tags($validated['content_html']);
        $validated['status'] = $request->input('action') === 'send_now' ? 'scheduled' : 'draft';
        if ($request->input('action') === 'send_now') {
            $validated['scheduled_at'] = now();
        }

        $campaign = EmailCampaign::create($validated);

        if ($request->input('action') === 'send_now') {
            dispatch(new SendCampaignEmails($campaign));
        }

        return redirect()->route('admin.campaigns.index')->with('success', 'Campaign saved.');
    }

    public function edit(EmailCampaign $campaign)
    {
        $segments = $this->segments();
        return view('admin.campaigns.edit', compact('campaign', 'segments'));
    }

    public function update(Request $request, EmailCampaign $campaign)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'from_name' => 'nullable|string|max:255',
            'from_email' => 'nullable|email|max:255',
            'test_email' => 'nullable|email|max:255',
            'content_html' => 'required|string',
            'segment' => 'required|string|in:' . implode(',', array_keys($this->segments())),
            'scheduled_at' => 'nullable|date',
        ]);

        $validated['content_text'] = strip_tags($validated['content_html']);

        if ($campaign->status === 'draft' && $request->input('action') === 'send_now') {
            $validated['status'] = 'scheduled';
            $validated['scheduled_at'] = now();
        }

        $campaign->update($validated);

        if ($campaign->status === 'scheduled' && $request->input('action') === 'send_now') {
            dispatch(new SendCampaignEmails($campaign));
        }

        return redirect()->route('admin.campaigns.index')->with('success', 'Campaign updated.');
    }

    public function show(EmailCampaign $campaign)
    {
        $recipients = $campaign->recipients()->latest()->paginate(50);
        return view('admin.campaigns.show', compact('campaign', 'recipients'));
    }

    public function destroy(EmailCampaign $campaign)
    {
        $campaign->delete();
        return redirect()->route('admin.campaigns.index')->with('success', 'Campaign deleted.');
    }

    public function send(EmailCampaign $campaign)
    {
        if (!in_array($campaign->status, ['draft', 'scheduled'])) {
            return back()->with('error', 'Campaign cannot be sent now.');
        }

        $campaign->update(['status' => 'scheduled', 'scheduled_at' => now()]);
        dispatch(new SendCampaignEmails($campaign));

        return back()->with('success', 'Campaign dispatch started.');
    }

    protected function segments(): array
    {
        return [
            'all' => 'All users + newsletter subscribers',
            'clients' => 'Clients (registered users)',
            'active' => 'Active clients + newsletter subscribers',
            'newsletter' => 'Newsletter subscribers only',
        ];
    }
}
