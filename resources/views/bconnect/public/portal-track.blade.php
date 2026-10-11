<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Track Ticket — BMyDesk</title>
    <link rel="icon" href="/img/bmydesk-icon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script>tailwind.config={theme:{extend:{colors:{brand:'#e11d48'}}}}</script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
<nav class="bg-white border-b border-slate-100">
    <div class="max-w-3xl mx-auto px-4 py-4 flex items-center justify-between">
        <a href="/" class="text-xl font-extrabold text-slate-900">BMy<span class="text-brand">Desk</span></a>
        <a href="/support/portal" class="text-sm text-slate-500 hover:text-brand"><i class="fas fa-arrow-left mr-1"></i>New ticket</a>
    </div>
</nav>
<div class="max-w-2xl mx-auto px-4 py-14">
    <h1 class="text-3xl font-extrabold text-slate-900 mb-8">Track your ticket</h1>

    <form method="GET" action="{{ route('bconnect.portal.track') }}" class="flex gap-3 mb-10">
        <input name="token" value="{{ $token }}" placeholder="Tracking code" class="flex-1 px-4 py-3 rounded-xl border border-slate-200 focus:border-brand outline-none font-mono text-sm">
        <button class="px-6 py-3 bg-slate-900 text-white font-bold rounded-xl">Check</button>
    </form>

    @if($t)
        <div class="bg-white rounded-2xl border border-slate-100 p-8">
            <div class="flex items-start justify-between mb-6">
                <div>
                    <p class="text-xs text-slate-400 font-mono">#{{ $t->id }}</p>
                    <h2 class="text-xl font-bold text-slate-900">{{ $t->title }}</h2>
                    <p class="text-sm text-slate-500 mt-1">Opened {{ $t->created_at->diffForHumans() }}</p>
                </div>
                <span class="px-3 py-1.5 rounded-full text-xs font-bold
                    {{ $t->status === 'open' ? 'bg-blue-50 text-blue-600' : ($t->status === 'resolved' || $t->status === 'closed' ? 'bg-green-50 text-green-600' : 'bg-amber-50 text-amber-600') }}">
                    {{ strtoupper(str_replace('_',' ', $t->status)) }}
                </span>
            </div>
            @php $pub = \App\Models\Bconnect\TicketComment::where('ticket_id', $t->id)->where('is_internal', false)->latest()->get(); @endphp
            @if($pub->isNotEmpty())
                <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-3">Latest replies</h3>
                <div class="space-y-4">
                    @foreach($pub->take(5) as $c)
                        <div class="bg-slate-50 rounded-xl p-4">
                            <p class="text-xs text-slate-400 mb-1">{{ $c->member_id ? 'Support team' : 'You' }} · {{ $c->created_at->diffForHumans() }}</p>
                            <p class="text-sm text-slate-700">{{ $c->body }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @elseif($token)
        <div class="bg-amber-50 border border-amber-200 text-amber-700 rounded-2xl p-5">No ticket found for that code — check the link you received.</div>
    @endif
</div>
</body>
</html>
