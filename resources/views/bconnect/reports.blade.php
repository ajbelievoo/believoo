@extends('bconnect.layout')
@section('title', 'Reports')
@section('content')
<div class="flex justify-between items-center mb-6">
    <h3 class="font-bold text-xl">Reports & Analytics</h3>
    <form method="GET" action="{{ route('bconnect.reports') }}" class="flex gap-2 items-end">
        <div>
            <label class="block text-xs text-slate-400 mb-1">Start</label>
            <input type="date" name="start" value="{{ $start->format('Y-m-d') }}" class="bc-input text-sm py-1.5">
        </div>
        <div>
            <label class="block text-xs text-slate-400 mb-1">End</label>
            <input type="date" name="end" value="{{ $end->format('Y-m-d') }}" class="bc-input text-sm py-1.5">
        </div>
        <button type="submit" class="bc-btn bc-btn-primary text-sm py-1.5"><i class="fas fa-filter mr-1"></i>Filter</button>
    </form>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bc-card p-5">
        <div class="text-2xl font-black text-cyan-400">{{ $tickets->count() }}</div>
        <div class="text-slate-400 text-sm">Tickets created</div>
    </div>
    <div class="bc-card p-5">
        <div class="text-2xl font-black text-green-400">{{ $resolvedTickets->count() }}</div>
        <div class="text-slate-400 text-sm">Resolved/closed</div>
    </div>
    <div class="bc-card p-5">
        <div class="text-2xl font-black text-amber-400">{{ number_format($avgResolutionSeconds / 3600, 1) }}h</div>
        <div class="text-slate-400 text-sm">Avg resolution time</div>
    </div>
    <div class="bc-card p-5">
        <div class="text-2xl font-black text-pink-400">{{ $totalLoggedHours }}h</div>
        <div class="text-slate-400 text-sm">Total logged</div>
    </div>
    <div class="bc-card p-5">
        <div class="text-2xl font-black text-green-400">₹{{ number_format($revenue, 2) }}</div>
        <div class="text-slate-400 text-sm">Revenue (paid)</div>
    </div>
    <div class="bc-card p-5">
        <div class="text-2xl font-black text-red-400">₹{{ number_format($outstanding, 2) }}</div>
        <div class="text-slate-400 text-sm">Outstanding</div>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-6 mb-6">
    <div class="bc-card p-6">
        <h4 class="font-bold mb-4">Projects</h4>
        <table class="bc-table">
            <thead><tr><th>Project</th><th>Open</th><th>Resolved</th><th>Total</th></tr></thead>
            <tbody>
                @forelse($projectStats as $p)
                <tr>
                    <td>{{ $p['name'] }}</td>
                    <td>{{ $p['open'] }}</td>
                    <td class="text-green-400">{{ $p['resolved'] }}</td>
                    <td>{{ $p['total'] }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="bc-empty">No projects.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="bc-card p-6">
        <h4 class="font-bold mb-4">Member Utilization</h4>
        <table class="bc-table">
            <thead><tr><th>Member</th><th>Hours</th><th>Billable</th><th>Revenue</th></tr></thead>
            <tbody>
                @forelse($memberStats as $m)
                <tr>
                    <td>{{ $m['name'] }}</td>
                    <td>{{ $m['hours'] }}</td>
                    <td>{{ $m['billable_hours'] }}</td>
                    <td>₹{{ number_format($m['billed_amount'], 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="bc-empty">No time entries.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="bc-card p-6">
    <h4 class="font-bold mb-4">Ticket Trend</h4>
    <div class="overflow-x-auto">
        <table class="bc-table">
            <thead><tr><th>Date</th><th>Created</th></tr></thead>
            <tbody>
                @forelse($dailyTickets as $day => $count)
                <tr><td>{{ \Carbon\Carbon::parse($day)->format('M d, Y') }}</td><td>{{ $count }}</td></tr>
                @empty
                <tr><td colspan="2" class="bc-empty">No data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
