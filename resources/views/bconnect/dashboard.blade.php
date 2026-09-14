@extends('bconnect.layout')
@section('title', 'Dashboard')
@section('content')
<div class="mb-8">
    <h2 class="text-2xl font-bold text-white mb-1">Welcome back, {{ auth()->user()?->name ?? 'Team' }}</h2>
    <p class="text-slate-400 text-sm">Here is your workspace overview.</p>
</div>
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <div class="bg-slate-900 p-6 rounded-2xl border border-slate-800 hover:border-cyan-500/30 transition">
        <div class="text-3xl font-black text-cyan-400">{{ $stats['projects'] }}</div>
        <div class="text-slate-400 text-sm">Projects</div>
    </div>
    <div class="bg-slate-900 p-6 rounded-2xl border border-slate-800 hover:border-pink-500/30 transition">
        <div class="text-3xl font-black text-pink-400">{{ $stats['open_tickets'] }}</div>
        <div class="text-slate-400 text-sm">Open Tickets</div>
    </div>
    <div class="bg-slate-900 p-6 rounded-2xl border border-slate-800 hover:border-green-500/30 transition">
        <div class="text-3xl font-black text-green-400">{{ $stats['meetings'] }}</div>
        <div class="text-slate-400 text-sm">Meetings</div>
    </div>
    <div class="bg-slate-900 p-6 rounded-2xl border border-slate-800 hover:border-amber-500/30 transition">
        <div class="text-3xl font-black text-amber-400">₹{{ number_format($stats['invoices'], 2) }}</div>
        <div class="text-slate-400 text-sm">Revenue</div>
    </div>
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
    <div class="lg:col-span-2 bg-slate-900 rounded-2xl border border-slate-800 p-6">
        <h3 class="font-bold text-white mb-4">Recent Tickets</h3>
        <table class="w-full text-sm">
            <thead><tr class="text-left text-slate-400 border-b border-slate-700"><th>Ticket</th><th>Project</th><th>Status</th><th>Priority</th></tr></thead>
            <tbody>
                @forelse($recentTickets as $t)
                <tr class="border-b border-slate-800">
                    <td class="py-3"><a href="{{ route('bconnect.tickets.show', $t->id) }}" class="text-cyan-400 hover:underline">{{ $t->title }}</a></td>
                    <td class="text-slate-400">{{ $t->project?->name ?? '—' }}</td>
                    <td><span class="px-2 py-1 rounded text-[10px] font-bold bg-slate-700">{{ ucfirst($t->status) }}</span></td>
                    <td><span class="px-2 py-1 rounded text-[10px] font-bold {{ $t->priority == 'critical' ? 'bg-red-500/20 text-red-400' : ($t->priority == 'high' ? 'bg-amber-500/20 text-amber-400' : 'bg-slate-700') }}">{{ ucfirst($t->priority) }}</span></td>
                </tr>
                @empty
                <tr><td colspan="4" class="py-6 text-center text-slate-500">No tickets yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="bg-slate-900 rounded-2xl border border-slate-800 p-6">
        <h3 class="font-bold text-white mb-4">Quick Links</h3>
        <div class="space-y-3">
            <a href="{{ route('bconnect.remote') }}" class="flex items-center gap-3 p-3 rounded-xl bg-slate-800 hover:bg-slate-700 transition text-sm text-slate-200">
                <i class="fas fa-desktop text-cyan-400"></i> Remote Desktop
            </a>
            <a href="{{ route('bconnect.files') }}" class="flex items-center gap-3 p-3 rounded-xl bg-slate-800 hover:bg-slate-700 transition text-sm text-slate-200">
                <i class="fas fa-folder-open text-cyan-400"></i> File Manager
            </a>
            <a href="{{ route('bconnect.company') }}" class="flex items-center gap-3 p-3 rounded-xl bg-slate-800 hover:bg-slate-700 transition text-sm text-slate-200">
                <i class="fas fa-building text-cyan-400"></i> Company Settings
            </a>
            <a href="{{ route('bconnect.audit') }}" class="flex items-center gap-3 p-3 rounded-xl bg-slate-800 hover:bg-slate-700 transition text-sm text-slate-200">
                <i class="fas fa-shield-alt text-cyan-400"></i> Audit Logs
            </a>
        </div>
    </div>
</div>
@endsection
