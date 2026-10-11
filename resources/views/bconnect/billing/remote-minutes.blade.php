@extends('bconnect.layout')
@section('title', 'Remote Minutes Billing')
@section('content')
<div class="bc-card p-6 max-w-3xl">
    <h3 class="font-bold mb-2">Bill remote-session minutes</h3>
    <p class="text-xs text-slate-500 mb-6">Un-billed remote sessions grouped by the member who connected. Set an hourly rate, pick the client to bill, done.</p>
    <table class="bc-table">
        <thead><tr><th>Member</th><th>Sessions</th><th>Minutes</th><th>Bill to client</th></tr></thead>
        <tbody>
        @forelse($sessions as $s)
            @php $mem = \App\Models\Bconnect\Member::with('user')->find($s->viewer_member_id); @endphp
            <tr>
                <td class="font-medium">{{ $mem?->user?->name ?? 'Member #'.$s->viewer_member_id }}</td>
                <td>{{ $s->sessions }}</td>
                <td>{{ round($s->secs / 60, 1) }} min</td>
                <td>
                    <form method="POST" action="{{ route('bconnect.billing.remote-minutes.store') }}" class="flex gap-2 items-center">@csrf
                        <input type="hidden" name="member_id" value="{{ $s->viewer_member_id }}">
                        <select name="client_id" required class="bc-input !w-auto text-xs"><option value="">Client…</option>@foreach($clients as $c)<option value="{{ $c->id }}">{{ $c->user->name }}</option>@endforeach</select>
                        <input name="rate_per_hour" type="number" step="0.01" min="0" placeholder="₹/hr" required class="bc-input !w-24 text-xs">
                        <button class="bc-btn bc-btn-primary text-xs">Invoice</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="bc-empty">No un-billed remote sessions.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
