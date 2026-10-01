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
        $companyId = $r->input('bconnect_company_id');
        $tickets = Ticket::with('project', 'reporter.user', 'assignee.user', 'sprint')
            ->where('company_id', $companyId)
            ->when($r->input('bconnect_role') === 'client', function($q) use ($r) {
                $projectIds = \App\Models\Bconnect\Project::where('client_id', $r->input('bconnect_member')->id)->pluck('id');
                $q->whereIn('project_id', $projectIds);
            })
            ->when($r->status, fn($q) => $q->where('status', $r->status))
            ->when($r->project_id, fn($q) => $q->where('project_id', $r->project_id))
            ->when($r->assignee_id, function($q) use ($r) {
                if ($r->assignee_id === 'unassigned') {
                    $q->whereNull('assignee_id');
                } else {
                    $q->where('assignee_id', $r->assignee_id);
                }
            })
            ->when($r->sprint_id, fn($q) => $q->where('sprint_id', $r->sprint_id))
            ->when($r->priority, fn($q) => $q->where('priority', $r->priority))
            ->latest()->paginate(20);
        $projects = Project::where('company_id', $companyId)->get();
        $sprints = \App\Models\Bconnect\Sprint::where('company_id', $companyId)->orderBy('start_date', 'desc')->get();
        $members = \App\Models\Bconnect\Member::with('user')->where('company_id', $companyId)->where('is_active', true)->get();
        $canCreate = \App\Services\BconnectPlanService::canCreateTicket($companyId);
        $ticketUsage = \App\Models\Bconnect\Ticket::where('company_id', $companyId)->count();
        $ticketLimit = \App\Services\BconnectPlanService::check($companyId, 'tickets');
        return view('bconnect.tickets', compact('tickets', 'projects', 'sprints', 'members', 'canCreate', 'ticketUsage', 'ticketLimit'));
    }

    public function store(Request $r) {
        if (!\App\Services\BconnectPlanService::canCreateTicket($r->input('bconnect_company_id'))) {
            return back()->with('error', 'Ticket limit reached for your plan. Upgrade to Pro/Enterprise.');
        }
        $companyId = $r->input('bconnect_company_id');
        $data = $r->validate([
            'project_id' => ['required', Rule::exists('bconnect_projects', 'id')->where('company_id', $companyId)],
            'title' => 'required',
            'description' => 'required',
            'type' => 'required|in:bug,feature,task,question,support',
            'priority' => 'required|in:low,medium,high,critical',
            'assignee_id' => ['nullable', Rule::exists('bconnect_members', 'id')->where('company_id', $companyId)],
            'sprint_id' => ['nullable', Rule::exists('bconnect_sprints', 'id')->where('company_id', $companyId)],
            'parent_id' => ['nullable', Rule::exists('bconnect_tickets', 'id')->where('company_id', $companyId)],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
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
            'company_id' => $companyId,
            'project_id' => $data['project_id'],
            'reporter_id' => $r->input('bconnect_member')->id,
            'assignee_id' => $data['assignee_id'] ?? null,
            'sprint_id' => $data['sprint_id'] ?? null,
            'parent_id' => $data['parent_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'],
            'type' => $data['type'],
            'status' => 'open',
            'priority' => $data['priority'],
            'start_date' => $data['start_date'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'estimated_hours' => $data['estimated_hours'] ?? null,
            'ai_suggested_priority' => $aiPriority,
            'ai_tags' => $aiTags,
            'ai_summary' => $aiSummary,
            'attachments' => $attachments,
            'position' => 0,
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
            url('https://bmydesk.believoo.com' . $ticketUrl),
            'View Ticket'
        );
        BconnectMail::toCompanyAdmins($r->input('bconnect_company_id'),
            'New Bmydesk ticket: ' . $ticket->title,
            'New ticket created',
            ['A new ticket has been created in your workspace.', 'Title: <strong>' . e($ticket->title) . '</strong><br>Priority: ' . e($ticket->priority) . '<br>Type: ' . e($ticket->type)],
            url('https://bmydesk.believoo.com' . $ticketUrl),
            'View Ticket'
        );

        \App\Services\BconnectWebhookService::dispatch($companyId, 'ticket.created', [
            'ticket_id' => $ticket->id,
            'title' => $ticket->title,
            'status' => $ticket->status,
            'priority' => $ticket->priority,
        ]);

        return redirect()->route('bconnect.tickets.show', $ticket->id)->with('success', 'Ticket created. AI suggestions applied.');
    }

    public function show(Request $r, Ticket $ticket) {
        if ($ticket->company_id != $r->input('bconnect_company_id')) abort(403);
        $ticket->load('project', 'reporter.user', 'assignee.user', 'sprint', 'parent', 'children', 'timeEntries');
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

        \App\Services\BconnectSlaTracker::recordFirstResponse($ticket);

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
                url('https://bmydesk.believoo.com' . $ticketUrl),
                'View Ticket'
            );
            \App\Services\BconnectNotificationService::send($member, 'ticket_comment', 'New comment', $commenter->user->name . ' commented on ' . $ticket->title, url('https://bmydesk.believoo.com' . $ticketUrl), $ticket->company_id);
        }

        return back()->with('success', 'Comment added');
    }

    public function updateStatus(Request $r, Ticket $ticket) {
        if ($ticket->company_id != $r->input('bconnect_company_id')) abort(403);
        $old = $ticket->status;
        $data = $r->validate(['status' => 'required|in:open,in_progress,testing,resolved,closed']);
        $ticket->update($data);
        if ($ticket->status == 'resolved') $ticket->update(['resolved_at' => now()]);
        else $ticket->update(['resolved_at' => null]);

        if ($old !== $ticket->status) {
            $url = route('bconnect.tickets.show', $ticket->id);
            $actor = $r->input('bconnect_member')->user->name;
            $recipients = collect([$ticket->reporter, $ticket->assignee])->filter()->unique('id')->where('id', '!=', $r->input('bconnect_member')->id);
            foreach ($recipients as $member) {
                \App\Services\BconnectNotificationService::send($member, 'ticket_status', 'Ticket status changed', "{$actor} changed status of {$ticket->title} from {$old} to {$ticket->status}", $url, $ticket->company_id);
            }
        }

        \App\Services\BconnectWebhookService::dispatch($ticket->company_id, 'ticket.status_changed', [
            'ticket_id' => $ticket->id,
            'title' => $ticket->title,
            'old_status' => $old,
            'new_status' => $ticket->status,
        ]);

        return back()->with('success', 'Status updated');
    }

    public function edit(Request $r, Ticket $ticket) {
        if ($ticket->company_id != $r->input('bconnect_company_id')) abort(403);
        $companyId = $r->input('bconnect_company_id');
        $projects = Project::where('company_id', $companyId)->get();
        $sprints = \App\Models\Bconnect\Sprint::where('company_id', $companyId)->orderBy('start_date', 'desc')->get();
        $members = \App\Models\Bconnect\Member::with('user')->where('company_id', $companyId)->where('is_active', true)->get();
        return view('bconnect.tickets.edit', compact('ticket', 'projects', 'sprints', 'members'));
    }

    public function update(Request $r, Ticket $ticket) {
        if ($ticket->company_id != $r->input('bconnect_company_id')) abort(403);
        $companyId = $r->input('bconnect_company_id');
        $data = $r->validate([
            'project_id' => ['required', Rule::exists('bconnect_projects', 'id')->where('company_id', $companyId)],
            'title' => 'required',
            'description' => 'required',
            'type' => 'required|in:bug,feature,task,question,support',
            'priority' => 'required|in:low,medium,high,critical',
            'status' => 'required|in:open,in_progress,testing,resolved,closed',
            'assignee_id' => ['nullable', Rule::exists('bconnect_members', 'id')->where('company_id', $companyId)],
            'sprint_id' => ['nullable', Rule::exists('bconnect_sprints', 'id')->where('company_id', $companyId)],
            'parent_id' => ['nullable', Rule::exists('bconnect_tickets', 'id')->where('company_id', $companyId), 'different:ticket'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
        ]);

        if ($data['status'] == 'resolved') $data['resolved_at'] = now();
        else $data['resolved_at'] = null;

        $attachments = $ticket->attachments ?? [];
        if ($r->hasFile('attachments')) {
            foreach ($r->file('attachments') as $file) {
                $attachments[] = $file->store('bconnect/tickets', 'public');
            }
        }

        if ($r->has('remove_attachments') && is_array($r->remove_attachments)) {
            foreach ($r->remove_attachments as $path) {
                if (in_array($path, $attachments, true)) {
                    Storage::disk('public')->delete($path);
                    $attachments = array_values(array_diff($attachments, [$path]));
                }
            }
        }

        $data['attachments'] = $attachments;
        $ticket->update($data);

        if ($ticket->assignee_id) {
            \App\Models\Bconnect\Notification::create([
                'company_id' => $companyId,
                'member_id' => $ticket->assignee_id,
                'type' => 'ticket',
                'title' => 'Ticket assigned: ' . $ticket->title,
                'message' => 'You have been assigned to ticket #' . $ticket->id,
                'url' => route('bconnect.tickets.show', $ticket->id),
            ]);
        }

        \App\Services\BconnectWebhookService::dispatch($companyId, 'ticket.updated', [
            'ticket_id' => $ticket->id,
            'title' => $ticket->title,
            'status' => $ticket->status,
            'priority' => $ticket->priority,
        ]);

        return redirect()->route('bconnect.tickets.show', $ticket->id)->with('success', 'Ticket updated');
    }

    public function destroy(Request $r, Ticket $ticket) {
        if ($ticket->company_id != $r->input('bconnect_company_id')) abort(403);
        if (!empty($ticket->attachments)) {
            foreach ($ticket->attachments as $path) Storage::disk('public')->delete($path);
        }
        $ticket->delete();
        return redirect()->route('bconnect.tickets')->with('success', 'Ticket deleted');
    }
}
