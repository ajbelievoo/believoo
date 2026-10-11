<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Support Portal — BMyDesk</title>
    <meta name="description" content="Get help from the BMyDesk team — no account needed.">
    <link rel="icon" href="/img/bmydesk-icon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script>tailwind.config={theme:{extend:{colors:{brand:'#e11d48'}}}}</script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
<nav class="bg-white border-b border-slate-100">
    <div class="max-w-3xl mx-auto px-4 py-4 flex items-center justify-between">
        <a href="/" class="text-xl font-extrabold text-slate-900">BMy<span class="text-brand">Desk</span></a>
        <div class="flex gap-5 text-sm">
            <a href="/help" class="text-slate-500 hover:text-brand">Help</a>
            <a href="{{ route('bconnect.portal.track') }}" class="text-slate-500 hover:text-brand">Track ticket</a>
        </div>
    </div>
</nav>
<div class="max-w-2xl mx-auto px-4 py-14">
    <h1 class="text-4xl font-extrabold text-slate-900 mb-2">Contact Support</h1>
    <p class="text-slate-600 mb-10">Describe your issue — we'll reply on email. You'll get a tracking link to check status.</p>

    @if(session('created'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-2xl p-5 mb-8">
            <p class="font-bold"><i class="fas fa-check-circle mr-2"></i>Ticket created!</p>
            <p class="text-sm mt-1">Save your tracking link — you can check status anytime at <a class="underline font-semibold" href="{{ route('bconnect.portal.track', ['token' => request()->route('token')]) }}">your ticket page</a>.</p>
        </div>
    @endif
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-2xl p-5 mb-8">
            <ul class="text-sm list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('bconnect.portal.submit') }}" class="bg-white rounded-2xl border border-slate-100 p-8 space-y-5">
        @csrf
        <div class="grid sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Your name</label>
                <input name="name" value="{{ old('name') }}" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-brand focus:ring-2 focus:ring-brand/20 outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Email</label>
                <input name="email" type="email" value="{{ old('email') }}" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-brand focus:ring-2 focus:ring-brand/20 outline-none">
            </div>
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Subject</label>
            <input name="subject" value="{{ old('subject') }}" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-brand focus:ring-2 focus:ring-brand/20 outline-none">
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Priority</label>
            <select name="priority" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-brand outline-none">
                <option value="normal">Normal</option>
                <option value="high">High — something is broken</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Describe the issue</label>
            <textarea name="message" rows="6" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-brand focus:ring-2 focus:ring-brand/20 outline-none">{{ old('message') }}</textarea>
        </div>
        <button class="w-full py-3.5 bg-brand hover:bg-rose-700 text-white font-bold rounded-xl transition">Submit ticket</button>
    </form>
</div>
</body>
</html>
