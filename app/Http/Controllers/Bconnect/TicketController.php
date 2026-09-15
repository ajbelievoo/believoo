<?php
namespace App\Http\Controllers\Bconnect;
use App\Http\Controllers\Controller;
use App\Models\Bconnect\Project;
use App\Models\Bconnect\Ticket;
use App\Models\Bconnect\TicketComment;
use App\Models\Bconnect\Notification;
use App\Services\BconnectMail;
use App\Services\NotifyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TicketController extends Controller {
    public function index(Request $r) {
        $tickets = Ticket::with('project', 'reporter.user', 'assignee.user')
            ->where('company_id', $r->input('bconnect_company_id'))
            ->when($r->input('bconnect_role') === 'client', function($q) use ($r) {
                $projectIds = \App\Models\Bconnect\Project::where('client_id', $r->input('bconnect_member')->id)->pluck('id');
                $q->whereIn('project_id', $projectIds);
            })
            ->when($r->status, fn($q) => $q->where('status', $r->status))
            ->when($r->project_id, fn($q) => $q->where('project_id', $r->project_id))
            ->latest()->paginate(20);
        $projects = Project::where('company_id', $r->input('bconnect_company_id'))->get();
        $canCreate = \App\Services\BconnectPlanService::canCreateTicket($r->input('bconnect_company_id'));
        $ticketUsage = \App\Models\Bconnect\Ticket::where('company_id', $r->input('bconnect_company_id'))->count();
        $ticketLimit = \App\Services\BconnectPlanService::check($r->input('bconnect_company_id'), 'tickets');
        return view('bconnect.tickets', compact('tickets', 'projects', 'canCreate', 'ticketUsage', 'ticketLimit'));
    }

    public function store(Request $r) {
        if (!\App\Services\BconnectPlanService::canCreateTicket($r->input('bconnect_company_id'))) {
            return back()->with('error', 'Ticket limit reached for your plan. Upgrade to Pro/Enterprise.');
        }
        $data = $r->validate([
            'project_id' => ['required', Rule::exists('bconnect_projects', 'id')->where('company_id', $r->input('bconnect_company_id'))],
            'title' => 'required',
            'description' => 'required',
            'type' => 'required|in:bug,feature,task,question,support',
            'priority' => 'required|in:low,medium,high,critical',
        ]);
        $attachments = [];
        if ($r->hasFile('attachments')) {
            foreach ($r->file('attachments') as $file) {
                $attachments[] = $file->store('bconnect/tickets', 'public');
            }
        }

        // AI diagnostics (Enterprise only)
        $aiSummary = '';
        $aiTags = [];
        $aiPriority = null;
        $company = \App\Models\Bconnect\Company::find($r->input('bconnect_company_id'));
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
        $apiKey = ($company?->plan === 'enterprise') ? ($settings['ai_gemini_api_key'] ?? $settings['ai_api_key'] ?? '') : '';
        if ($apiKey) {
            try {
                $prompt = "Analyze this bug/ticket and return JSON with fields: title (improved 10 words), priority (low/medium/high/critical), tags (array of 3 keywords), summary (2 sentences).\n\nTitle: {$data['title']}\nDescription: {$data['description']}";
                $resp = \Illuminate\Support\Facades\Http::timeout(15)->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/" . ($settings['ai_gemini_model'] ?? 'gemini-1.5-flash') . ":generateContent?key={$apiKey}",
                    ['contents' => [['parts' => [['text' => $prompt]]]], 'generationConfig' => ['maxOutputTokens' => 300]]
                );
                if ($resp->successful()) {
                    $text = trim($resp->json('candidates.0.content.parts.0.text'));
                    if (preg_match('/\{.*\}/s', $text, $m)) {
                        $json = json_decode($m[0], true);
                        if ($json) {
                            $data['title'] = $json['title'] ?? $data['title'];
                            $aiPriority = $json['priority'] ?? null;
                            $aiTags = $json['tags'] ?? [];
                            $aiSummary = $json['summary'] ?? '';
                        }
                    }
                }
            } catch (\Exception $e) { \Log::error('AI ticket analysis failed: ' . $e->getMessage()); }
        }

        $ticket = Ticket::create([
            'company_id' => $r->input('bconnect_company_id'),
            'project_id' => $data['project_id'],
            'reporter_id' => $r->input('bconnect_member')->id,
            'assignee_id' => null,
            'title' => $data['title'],
            'description' => $data['description'],
            'type' => $data['type'],
            'status' => 'open',
            'priority' => $data['priority'],
            'ai_suggested_priority' => $aiPriority,
            'ai_tags' => $aiTags,
            'ai_summary' => $aiSummary,
            'attachments' => $attachments,
        ]);

        Notification::create([
            'company_id' => $r->input('bconnect_company_id'),
            'member_id' => $r->input('bconnect_member')->id,
            'type' => 'ticket',
            'title' => 'New ticket created',
            'message' => $ticket->title,
            'url' => route('bconnect.tickets.show', $ticket->id),
        ]);

        // Email the reporter and company admins
        $reporter = $r->input('bconnect_member');
        $ticketUrl = route('bconnect.tickets.show', $ticket->id, false);
        BconnectMail::toMember($reporter,
            'Ticket received: ' . $ticket->title,
            'Your ticket has been created',
            ['Hi ' . ($reporter->user->name ?? 'there') . ',', 'We received your ticket <strong>' . e($ticket->title) . '</strong>. Our team typically replies within 24 hours.'],
            url('https://bc.believoo.com' . $ticketUrl),
            'View Ticket'
        );
        BconnectMail::toCompanyAdmins($r->input('bconnect_company_id'),
            'New B-Connect ticket: ' . $ticket->title,
            'New ticket created',
            ['A new ticket has been created in your workspace.', 'Title: <strong>' . e($ticket->title) . '</strong><br>Priority: ' . e($ticket->priority) . '<br>Type: ' . e($ticket->type)],
            url('https://bc.believoo.com' . $ticketUrl),
            'View Ticket'
        );

        return redirect()->route('bconnect.tickets.show', $ticket->id)->with('success', 'Ticket created. AI suggestions applied.');
    }

    public function show(Request $r, Ticket $ticket) {
        if ($ticket->company_id != $r->input('bconnect_company_id')) abort(403);
        $comments = TicketComment::with('member.user')->where('ticket_id', $ticket->id)->latest()->get();
        return view('bconnect.ticket', compact('ticket', 'comments'));
    }

    public function comment(Request $r, Ticket $ticket) {
        if ($ticket->company_id != $r->input('bconnect_company_id')) abort(403);
        $data = $r->validate(['message' => 'required']);
        $attachments = [];
        if ($r->hasFile('attachments')) {
            foreach ($r->file('attachments') as $file) $attachments[] = $file->store('bconnect/tickets', 'public');
        }
        $comment = TicketComment::create(['ticket_id' => $ticket->id, 'member_id' => $r->input('bconnect_member')->id, 'message' => $data['message'], 'attachments' => $attachments]);

        // Notify ticket participants
        $commenter = $r->input('bconnect_member');
        $ticketUrl = route('bconnect.tickets.show', $ticket->id, false);
        $recipients = collect();
        if ($ticket->reporter && $ticket->reporter->id != $commenter->id) $recipients->push($ticket->reporter);
        if ($ticket->assignee && $ticket->assignee->id != $commenter->id) $recipients->push($ticket->assignee);
        foreach ($recipients as $member) {
            BconnectMail::toMember($member,
                'New comment on: ' . $ticket->title,
                'A new comment has been added',
                ['Hi ' . ($member->user->name ?? 'there') . ',', 'There is a new comment on ticket <strong>' . e($ticket->title) . '</strong>:', '<blockquote style="border-left:4px solid #00b7ff;padding-left:12px;margin:12px 0;color:#334155;">' . e($data['message']) . '</blockquote>'],
                url('https://bc.believoo.com' . $ticketUrl),
                'View Ticket'
            );
        }

        return back()->with('success', 'Comment added');
    }

    public function updateStatus(Request $r, Ticket $ticket) {
        if ($ticket->company_id != $r->input('bconnect_company_id')) abort(403);
        $ticket->update($r->validate(['status' => 'required|in:open,in_progress,resolved,closed,reopened']));
        if ($ticket->status == 'resolved') $ticket->update(['resolved_at' => now()]);
        return back()->with('success', 'Status updated');
    }
}
