@php
$companyId = request()->input('bconnect_company_id');
$projects = \App\Models\Bconnect\Project::where('company_id', $companyId)->get();
$canCreate = \App\Services\BconnectPlanService::canCreateTicket($companyId);
$ticketLimit = \App\Services\BconnectPlanService::check($companyId, 'tickets');
@endphp
@extends('bconnect.layout')
@section('title', 'Help & Support')
@section('content')
<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="bc-card p-6">
            <h2 class="text-2xl font-bold text-white mb-2">Help & Support</h2>
            <p class="text-slate-400 mb-6">Find guides or create a support ticket.</p>
            <div class="grid md:grid-cols-2 gap-4 mb-6">
                <a href="https://believoo.com/help" target="_blank" class="bc-card p-4 block hover:border-cyan-500/30 transition">
                    <i class="fas fa-book text-cyan-400 text-2xl mb-3"></i>
                    <h3 class="font-bold text-white mb-1">Knowledge Base</h3>
                    <p class="text-slate-400 text-sm">Detailed guides and tutorials.</p>
                </a>
                <a href="https://believoo.com/status" target="_blank" class="bc-card p-4 block hover:border-cyan-500/30 transition">
                    <i class="fas fa-chart-line text-cyan-400 text-2xl mb-3"></i>
                    <h3 class="font-bold text-white mb-1">System Status</h3>
                    <p class="text-slate-400 text-sm">Live service status and uptime.</p>
                </a>
            </div>

            <h3 class="font-bold mb-4">Quick Guides</h3>
            <div class="space-y-3">
                <details class="group bg-slate-800/50 rounded-xl p-4 border border-[var(--bc-border)]">
                    <summary class="font-semibold text-white cursor-pointer list-none flex justify-between items-center"><span>How do I start a video meeting?</span><i class="fas fa-chevron-down text-cyan-400 group-open:rotate-180 transition"></i></summary>
                    <p class="text-slate-400 text-sm mt-3">Go to <strong>Meetings</strong>, click <strong>Schedule / Start</strong>, fill title and time, then join the room.</p>
                </details>
                <details class="group bg-slate-800/50 rounded-xl p-4 border border-[var(--bc-border)]">
                    <summary class="font-semibold text-white cursor-pointer list-none flex justify-between items-center"><span>How do I create a ticket?</span><i class="fas fa-chevron-down text-cyan-400 group-open:rotate-180 transition"></i></summary>
                    <p class="text-slate-400 text-sm mt-3"><strong>Tickets</strong> menu &rarr; <strong>New Ticket</strong>. Set title, description and priority.</p>
                </details>
                <details class="group bg-slate-800/50 rounded-xl p-4 border border-[var(--bc-border)]">
                    <summary class="font-semibold text-white cursor-pointer list-none flex justify-between items-center"><span>How does billing work?</span><i class="fas fa-chevron-down text-cyan-400 group-open:rotate-180 transition"></i></summary>
                    <p class="text-slate-400 text-sm mt-3"><strong>Billing</strong> lets you create client invoices or upgrade your plan. Clients can pay via Razorpay/Cashfree.</p>
                </details>
            </div>
        </div>
    </div>

    <div class="bc-card p-6">
        <h3 class="font-bold mb-4">Create Support Ticket</h3>
        @if(!$canCreate)
        <div class="p-4 mb-4 rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20 text-sm">
            <i class="fas fa-exclamation-circle mr-2"></i>Ticket limit reached. <a href="{{ route('bconnect.billing.upgrade') }}" class="underline hover:text-white">Upgrade plan</a>.
        </div>
        @endif
        <form method="POST" action="{{ route('bconnect.tickets.store') }}" enctype="multipart/form-data" class="space-y-4">@csrf
            <select name="project_id" required class="bc-input" @if(!$canCreate) disabled @endif>
                <option value="">Select project / workspace</option>
                @foreach($projects as $p)
                <option value="{{ $p->id }}">{{ $p->name }}</option>
                @endforeach
            </select>
            <input type="text" name="title" placeholder="Issue title" required class="bc-input" @if(!$canCreate) disabled @endif>
            <textarea name="description" rows="4" placeholder="Describe the issue" required class="bc-input" @if(!$canCreate) disabled @endif></textarea>
            <select name="priority" class="bc-input" @if(!$canCreate) disabled @endif>
                <option value="low">Low</option>
                <option value="medium" selected>Medium</option>
                <option value="high">High</option>
                <option value="critical">Critical</option>
            </select>
            <input type="file" name="attachments[]" multiple class="bc-input text-sm" @if(!$canCreate) disabled @endif>
            <input type="hidden" name="type" value="support">
            <button type="submit" class="bc-btn bc-btn-primary w-full" @if(!$canCreate) disabled @endif><i class="fas fa-paper-plane"></i>Submit Ticket</button>
        </form>
        <p class="text-center text-slate-500 text-sm mt-4">Or email <a href="mailto:support@believoo.com" class="text-cyan-400 hover:underline">support@believoo.com</a></p>
    </div>
</div>
@endsection
