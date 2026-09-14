@extends('bconnect.layout')
@section('title', 'Audit Logs')
@section('content')
<div class="bg-slate-900 rounded-xl border border-slate-800 p-6">
    <h3 class="font-bold mb-4">Security Audit Logs</h3>
    <table class="w-full text-sm">
        <thead><tr class="text-left text-slate-400 border-b border-slate-700"><th>Time</th><th>Action</th><th>IP</th><th>Details</th></tr></thead>
        <tbody>
            @forelse($logs as $l)
            <tr class="border-b border-slate-800">
                <td class="py-3">{{ $l->created_at->format('d M H:i') }}</td>
                <td>{{ $l->action }}</td>
                <td><code class="text-xs">{{ $l->ip_address }}</code></td>
                <td class="text-slate-400 text-xs">{{ Str::limit(json_encode($l->details), 80) }}</td>
            </tr>
            @empty<tr><td colspan="4" class="py-6 text-center text-slate-500">No audit logs.</td></tr>@endforelse
        </tbody>
    </table>
    {{ $logs->links() }}
</div>
@endsection
