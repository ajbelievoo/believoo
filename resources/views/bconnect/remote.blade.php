@extends('bconnect.layout')
@section('title', 'Remote Desktop')
@section('content')
<div class="grid grid-cols-2 gap-6 mb-8">
    <div class="bg-slate-900 rounded-xl border border-slate-800 p-6">
        <h3 class="font-bold mb-4">Request Control</h3>
        <form method="POST" action="{{ route('bconnect.remote.request') }}" class="space-y-4">@csrf
            <select name="target_id" required class="w-full p-3 rounded-lg bg-slate-800 border border-slate-600 text-white"><option value="">Select user</option>@foreach(\App\Models\Bconnect\Member::with('user')->where('company_id', request()->input('bconnect_company_id'))->where('id', '!=', request()->input('bconnect_member')->id)->get() as $m)<option value="{{ $m->id }}">{{ $m->user->name }} ({{ ucfirst(str_replace('_',' ',$m->role)) }})</option>@endforeach</select>
            <select name="permission" class="w-full p-3 rounded-lg bg-slate-800 border border-slate-600 text-white"><option value="view">View Only</option><option value="control">Full Control</option><option value="clipboard">Clipboard Access</option></select>
            <button type="submit" class="w-full py-2 bg-green-500 text-white font-bold rounded-lg hover:bg-green-400">Send Request</button>
        </form>
    </div>
    <div class="bg-slate-900 rounded-xl border border-slate-800 p-6">
        <h3 class="font-bold mb-4">Incoming Requests</h3>
        <div class="space-y-3">
            @forelse($sessions as $s)
            <div class="bg-slate-800/50 rounded-lg p-4 flex justify-between items-center">
                <div>
                    <div class="font-bold">{{ $s->requester->user->name }}</div>
                    <div class="text-xs text-slate-400">Code: <code>{{ $s->session_code }}</code> • {{ ucfirst($s->permission) }}</div>
                </div>
                <div class="flex gap-2">
                    <form method="POST" action="{{ route('bconnect.remote.respond', $s->id) }}" class="inline">@csrf<input type="hidden" name="status" value="active"><button class="px-3 py-1 bg-green-500/20 text-green-400 rounded text-xs">Accept</button></form>
                    <form method="POST" action="{{ route('bconnect.remote.respond', $s->id) }}" class="inline">@csrf<input type="hidden" name="status" value="rejected"><button class="px-3 py-1 bg-red-500/20 text-red-400 rounded text-xs">Reject</button></form>
                </div>
            </div>
            @empty<p class="text-slate-500 text-sm">No pending requests.</p>@endforelse
        </div>
    </div>
</div>
<div class="bg-slate-900 rounded-xl border border-slate-800 p-6">
    <h3 class="font-bold mb-4">Remote Session Log</h3>
    <table class="w-full text-sm">
        <thead><tr class="text-left text-slate-400 border-b border-slate-700"><th>Code</th><th>Requester</th><th>Permission</th><th>Status</th></tr></thead>
        <tbody>@foreach($sessions as $s)<tr class="border-b border-slate-800"><td class="py-2">{{ $s->session_code }}</td><td>{{ $s->requester->user->name }}</td><td>{{ ucfirst($s->permission) }}</td><td>{{ ucfirst($s->status) }}</td></tr>@endforeach</tbody>
    </table>
</div>
@endsection
