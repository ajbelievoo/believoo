@extends('bconnect.layout')
@section('title', 'Remote Desktop')
@section('content')
<div class="bc-card p-6 mb-6 flex flex-col md:flex-row items-center gap-5" style="background:linear-gradient(135deg,rgba({{ $bconnectBrand['brand_rgb'] }},0.10),transparent 60%);">
    <div class="w-14 h-14 rounded-2xl flex items-center justify-center shrink-0" style="background:rgba({{ $bconnectBrand['brand_rgb'] }},0.15);">
        <i class="fas fa-plug text-2xl" style="color:var(--bc-cyan)"></i>
    </div>
    <div class="flex-1 text-center md:text-left">
        <h3 class="font-black text-lg">Connect with a Code</h3>
        <p class="text-xs text-slate-400">AnyDesk-style: enter the code shown on the host's BMyDesk Agent or browser host page — no team invite needed.</p>
    </div>
    <a href="{{ route('bconnect.remote.connect') }}" class="bc-btn bc-btn-primary px-8 py-3 shrink-0"><i class="fas fa-arrow-right mr-2"></i>Connect</a>
</div>

{{-- Address book — member-scoped saved devices with live online state --}}
<div class="bc-card p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="font-bold"><i class="fas fa-address-book mr-2" style="color:var(--bc-cyan)"></i>My Devices</h3>
        <form method="POST" action="{{ route('bconnect.remote.devices.save') }}" class="flex gap-2">@csrf
            <input name="code" maxlength="12" required placeholder="CODE" class="bc-input !w-32 font-mono uppercase text-sm">
            <input name="label" placeholder="Label (optional)" class="bc-input !w-40 text-sm">
            <button class="bc-btn bc-btn-secondary text-xs">Save</button>
        </form>
    </div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
        @forelse($savedDevices as $d)
        <div class="bg-slate-900/60 rounded-xl border border-slate-800 p-4 flex items-center gap-3">
            <span class="w-2.5 h-2.5 rounded-full shrink-0 {{ $d->online ? 'bg-green-400' : 'bg-slate-600' }}" title="{{ $d->online ? 'Online' : 'Offline' }}"></span>
            <div class="flex-1 min-w-0">
                <div class="font-bold text-sm truncate">{{ $d->label ?: 'Device' }}</div>
                <div class="text-[11px] font-mono text-slate-500">{{ $d->code }}{{ $d->host_label ? ' · '.$d->host_label : '' }}</div>
            </div>
            <a href="{{ route('bconnect.remote.code', $d->code) }}" class="bc-btn bc-btn-primary text-xs py-1 px-2.5">Connect</a>
            <form method="POST" action="{{ route('bconnect.remote.devices.forget', $d->id) }}" onsubmit="return confirm('Remove?')">@csrf @method('DELETE')
                <button class="text-slate-600 hover:text-red-400 text-xs"><i class="fas fa-trash"></i></button>
            </form>
        </div>
        @empty
        <p class="text-slate-500 text-sm col-span-3">No saved devices. Save a code after a successful connect or add one above.</p>
        @endforelse
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-6 mb-6">
    <div class="bc-card p-6">
        <h3 class="font-bold mb-4"><i class="fas fa-desktop mr-2 text-green-400"></i>Request Control</h3>
        <form method="POST" action="{{ route('bconnect.remote.request') }}" class="space-y-4">@csrf
            <select name="target_id" required class="bc-input"><option value="">Select user</option>@foreach($members as $m)<option value="{{ $m->id }}">{{ $m->user->name }} ({{ ucfirst(str_replace('_',' ',$m->role)) }})</option>@endforeach</select>
            <select name="permission" class="bc-input">
                <option value="view">View Only</option>
                <option value="control">Request Full Control (requires host approval + agent)</option>
                <option value="clipboard">Clipboard Access</option>
            </select>
            <p class="text-xs text-slate-500">Full OS mouse/keyboard control needs the host to approve and run the desktop agent. Browser-based view works without agent.</p>
            <button type="submit" class="bc-btn bc-btn-primary w-full"><i class="fas fa-share-square mr-1"></i>Send Request</button>
        </form>
    </div>
    <div class="bc-card p-6">
        <h3 class="font-bold mb-4"><i class="fas fa-bell mr-2 text-amber-400"></i>Incoming Requests</h3>
        <div class="space-y-3">
            @forelse($sessions->where('status', 'pending') as $s)
            <div class="bg-slate-900 rounded-lg p-4 flex justify-between items-center flex-wrap gap-3 border border-slate-800">
                <div>
                    <div class="font-bold">{{ $s->requester->user->name }}</div>
                    <div class="text-xs text-slate-400">Code: <code>{{ $s->session_code }}</code> • {{ ucfirst($s->permission) }}</div>
                </div>
                <div class="flex gap-2 shrink-0">
                    <form method="POST" action="{{ route('bconnect.remote.respond', $s->id) }}" class="inline">@csrf<input type="hidden" name="status" value="active"><button class="bc-btn bc-btn-primary text-xs py-1 px-2">Accept</button></form>
                    <form method="POST" action="{{ route('bconnect.remote.respond', $s->id) }}" class="inline">@csrf<input type="hidden" name="status" value="rejected"><button class="bc-btn bc-btn-danger text-xs py-1 px-2">Reject</button></form>
                </div>
            </div>
            @empty<p class="text-slate-500 text-sm">No pending requests.</p>@endforelse
        </div>
    </div>
</div>

<div class="bc-card p-6">
    <h3 class="font-bold mb-4">Remote Session Log</h3>
    <div class="overflow-x-auto">
        <table class="bc-table">
            <thead><tr><th>Code</th><th>Requester</th><th>Target</th><th>Permission</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
                @foreach($sessions as $s)
                <tr>
                    <td class="font-mono">{{ $s->session_code }}</td>
                    <td>{{ $s->requester?->user?->name ?? $s->host_label ?? '—' }}{{ $s->host_kind === 'agent' ? ' (agent)' : '' }}</td>
                    <td>{{ $s->target?->user?->name ?? $s->viewer?->user?->name ?? '—' }}</td>
                    <td>{{ ucfirst($s->permission) }}</td>
                    <td><span class="bc-badge {{ $s->status == 'active' ? 'bc-badge-green' : ($s->status == 'rejected' ? 'bc-badge-red' : 'bc-badge-amber') }}">{{ ucfirst($s->status) }}</span></td>
                    <td>
                        @if($s->status == 'active')
                        <a href="{{ $s->host_kind === 'member' && $s->company_id ? route('bconnect.remote.room', $s->id) : route('bconnect.remote.code', $s->session_code) }}" class="bc-btn bc-btn-primary text-xs py-1 px-2">Join</a>
                        <form method="POST" action="{{ route('bconnect.remote.respond', $s->id) }}" class="inline">@csrf<input type="hidden" name="status" value="ended"><button class="bc-btn bc-btn-danger text-xs py-1 px-2">End</button></form>
                        @elseif($s->status == 'pending' && $s->target_id == request()->input('bconnect_member')->id)
                        <form method="POST" action="{{ route('bconnect.remote.respond', $s->id) }}" class="inline">@csrf<input type="hidden" name="status" value="active"><button class="bc-btn bc-btn-primary text-xs py-1 px-2">Accept</button></form>
                        @else
                        <span class="text-slate-500 text-xs">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $sessions->links() }}
</div>
@endsection
