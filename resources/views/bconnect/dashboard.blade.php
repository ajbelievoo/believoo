@extends('bconnect.layout')
@section('title', 'Dashboard')
@section('content')
<div class="mb-8">
    <h2 class="text-2xl font-bold text-white mb-1">Welcome back, {{ auth()->user()?->name ?? 'Team' }}</h2>
    <p class="text-slate-400 text-sm">Here is your workspace overview.</p>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <a href="{{ route('bconnect.projects.index') }}" class="bc-card bc-card-hover p-5 block">
        <div class="flex items-center justify-between mb-3">
            <div class="text-3xl font-black text-cyan-400">{{ $stats['projects'] }}</div>
            <div class="w-10 h-10 rounded-full bg-cyan-500/10 flex items-center justify-center text-cyan-400"><i class="fas fa-folder"></i></div>
        </div>
        <div class="text-slate-400 text-sm">Projects</div>
    </a>
    <a href="{{ route('bconnect.tickets') }}" class="bc-card bc-card-hover p-5 block">
        <div class="flex items-center justify-between mb-3">
            <div class="text-3xl font-black text-pink-400">{{ $stats['open_tickets'] }}</div>
            <div class="w-10 h-10 rounded-full bg-pink-500/10 flex items-center justify-center text-pink-400"><i class="fas fa-bug"></i></div>
        </div>
        <div class="text-slate-400 text-sm">Open Tickets</div>
    </a>
    <a href="{{ route('bconnect.meetings') }}" class="bc-card bc-card-hover p-5 block">
        <div class="flex items-center justify-between mb-3">
            <div class="text-3xl font-black text-green-400">{{ $stats['meetings'] }}</div>
            <div class="w-10 h-10 rounded-full bg-green-500/10 flex items-center justify-center text-green-400"><i class="fas fa-video"></i></div>
        </div>
        <div class="text-slate-400 text-sm">Meetings</div>
    </a>
    <a href="{{ route('bconnect.billing') }}" class="bc-card bc-card-hover p-5 block">
        <div class="flex items-center justify-between mb-3">
            <div class="text-3xl font-black text-amber-400">₹{{ number_format($stats['invoices'], 2) }}</div>
            <div class="w-10 h-10 rounded-full bg-amber-500/10 flex items-center justify-center text-amber-400"><i class="fas fa-rupee-sign"></i></div>
        </div>
        <div class="text-slate-400 text-sm">Revenue</div>
    </a>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <a href="{{ route('bconnect.meetings') }}" class="bg-cyan-500/10 hover:bg-cyan-500/20 border border-cyan-500/30 p-5 rounded-2xl text-center transition">
        <i class="fas fa-video text-cyan-400 text-2xl mb-2"></i>
        <div class="font-bold text-cyan-400 text-sm">New Meeting</div>
    </a>
    <a href="{{ route('bconnect.tickets') }}" class="bg-pink-500/10 hover:bg-pink-500/20 border border-pink-500/30 p-5 rounded-2xl text-center transition">
        <i class="fas fa-ticket-alt text-pink-400 text-2xl mb-2"></i>
        <div class="font-bold text-pink-400 text-sm">New Ticket</div>
    </a>
    <a href="{{ route('bconnect.projects.create') }}" class="bg-green-500/10 hover:bg-green-500/20 border border-green-500/30 p-5 rounded-2xl text-center transition">
        <i class="fas fa-folder-plus text-green-400 text-2xl mb-2"></i>
        <div class="font-bold text-green-400 text-sm">New Project</div>
    </a>
    <a href="{{ route('bconnect.billing') }}" class="bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/30 p-5 rounded-2xl text-center transition">
        <i class="fas fa-file-invoice text-amber-400 text-2xl mb-2"></i>
        <div class="font-bold text-amber-400 text-sm">New Invoice</div>
    </a>
</div>

<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="bc-card p-6">
            <h3 class="font-bold text-white mb-4">Recent Tickets</h3>
            <table class="bc-table">
                <thead><tr><th>Ticket</th><th>Project</th><th>Status</th><th>Priority</th></tr></thead>
                <tbody>
                    @forelse($recentTickets as $t)
                    <tr>
                        <td><a href="{{ route('bconnect.tickets.show', $t->id) }}" class="text-cyan-400 hover:underline font-medium">{{ $t->title }}</a></td>
                        <td class="text-slate-400">{{ $t->project?->name ?? '—' }}</td>
                        <td><span class="bc-badge bc-badge-slate">{{ ucfirst(str_replace('_', ' ', $t->status)) }}</span></td>
                        <td><span class="bc-badge {{ $t->priority == 'critical' ? 'bc-badge-red' : ($t->priority == 'high' ? 'bc-badge-amber' : 'bc-badge-slate') }}">{{ ucfirst($t->priority) }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="bc-empty">No tickets yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="bc-card p-6">
            <h3 class="font-bold text-white mb-4">Upcoming / Recent Meetings</h3>
            <table class="bc-table">
                <thead><tr><th>Meeting</th><th>Created By</th><th>Started</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($recentMeetings as $m)
                    <tr>
                        <td><a href="{{ route('bconnect.meeting.room', $m->room_id) }}" class="text-cyan-400 hover:underline font-medium">{{ $m->title }}</a></td>
                        <td class="text-slate-400">{{ $m->creator?->user?->name ?? '—' }}</td>
                        <td class="text-slate-400">{{ $m->started_at?->format('M d, H:i') ?? '—' }}</td>
                        <td><span class="bc-badge {{ $m->ended_at ? 'bc-badge-slate' : 'bc-badge-green' }}">{{ $m->ended_at ? 'Ended' : 'Live' }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="bc-empty">No meetings yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($pendingInvoices->count())
        <div class="bc-card p-6">
            <h3 class="font-bold text-white mb-4">Pending Invoices</h3>
            <table class="bc-table">
                <thead><tr><th>Invoice</th><th>Amount</th><th>Client</th><th>Action</th></tr></thead>
                <tbody>
                    @foreach($pendingInvoices as $i)
                    <tr>
                        <td>{{ $i->invoice_number }}</td>
                        <td>₹{{ number_format($i->amount, 2) }}</td>
                        <td class="text-slate-400">{{ $i->client?->user?->name ?? '—' }}</td>
                        <td><a href="{{ route('bconnect.billing.pay', $i->id) }}" class="bc-btn bc-btn-primary py-1 px-3 text-xs"><i class="fas fa-credit-card"></i>Pay</a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    <div class="space-y-6">
        <div class="bc-card p-6">
            <h3 class="font-bold text-white mb-4">Plan Status</h3>
            <div class="flex items-center justify-between mb-3">
                <span class="text-slate-400 text-sm">Current plan</span>
                <span class="bc-badge {{ $status == 'active' ? 'bc-badge-green' : ($status == 'expired' || str_starts_with($status, 'cancelled-expired') ? 'bc-badge-red' : ($status == 'grace-period' || str_starts_with($status, 'cancelled') ? 'bc-badge-amber' : 'bc-badge-slate')) }}">{{ ucfirst(str_replace('-', ' ', $status)) }}</span>
            </div>
            @if($days !== null)
            <div class="flex items-center justify-between mb-4">
                <span class="text-slate-400 text-sm">Days left</span>
                <span class="font-bold text-white">{{ $days }}</span>
            </div>
            @endif
            <div class="flex items-center justify-between mb-4">
                <span class="text-slate-400 text-sm">Members used</span>
                <span class="font-bold text-white">{{ $planLimits['member_usage'] }} / {{ $planLimits['members'] ?? '∞' }}</span>
            </div>
            <a href="{{ route('bconnect.billing.upgrade') }}" class="bc-btn bc-btn-primary w-full"><i class="fas fa-arrow-up"></i>Upgrade Plan</a>
        </div>

        <div class="bc-card p-6">
            <h3 class="font-bold text-white mb-4">Quick Links</h3>
            <div class="space-y-2">
                <a href="{{ route('bconnect.remote') }}" class="flex items-center gap-3 p-3 rounded-xl bg-slate-800/50 hover:bg-slate-800 transition text-sm text-slate-200"><i class="fas fa-desktop text-cyan-400 w-5"></i> Remote Desktop</a>
                <a href="{{ route('bconnect.files') }}" class="flex items-center gap-3 p-3 rounded-xl bg-slate-800/50 hover:bg-slate-800 transition text-sm text-slate-200"><i class="fas fa-folder-open text-cyan-400 w-5"></i> File Manager</a>
                <a href="{{ route('bconnect.company') }}" class="flex items-center gap-3 p-3 rounded-xl bg-slate-800/50 hover:bg-slate-800 transition text-sm text-slate-200"><i class="fas fa-building text-cyan-400 w-5"></i> Company Settings</a>
                <a href="{{ route('bconnect.audit') }}" class="flex items-center gap-3 p-3 rounded-xl bg-slate-800/50 hover:bg-slate-800 transition text-sm text-slate-200"><i class="fas fa-shield-alt text-cyan-400 w-5"></i> Audit Logs</a>
            </div>
        </div>
    </div>
</div>
@endsection
