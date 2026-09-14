<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Remote Desktop Agent | B-CONNECT</title>
<link rel="preconnect" href="https://fonts.bunny.net"><link href="https://fonts.bunny.net/css?family=figtree:400,600,800&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script>tailwind.config={theme:{extend:{colors:{brand:'#00B7FF'}}}}</script>
<style>html,body{font-family:Figtree,Inter,sans-serif;background:#f8fafc;}</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
<div class="max-w-2xl w-full bg-white rounded-2xl border border-slate-200 shadow-2xl p-8">
    <div class="text-center mb-8">
        <h1 class="text-3xl font-extrabold text-slate-900"><i class="fas fa-desktop text-brand mr-2"></i>Remote Desktop Agent</h1>
        <p class="text-slate-500 mt-2">Full OS remote control requires the B-CONNECT desktop agent.</p>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-green-800 text-sm mb-6">
        <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
    </div>
    @endif

    <div class="grid md:grid-cols-2 gap-6 mb-8">
        <div class="p-6 bg-slate-50 rounded-2xl text-center">
            <i class="fab fa-windows text-4xl text-blue-500 mb-3"></i>
            <h3 class="font-bold mb-2">Windows</h3>
            <form method="POST" action="{{ route('bconnect.remote.agent.request') }}" class="space-y-3 text-left">
                @csrf
                <input type="hidden" name="os" value="windows">
                <textarea name="message" rows="2" placeholder="Tell us about your use case (optional)" class="w-full p-2 rounded-lg bg-white border border-slate-200 text-sm"></textarea>
                <button type="submit" class="w-full px-4 py-2 bg-brand text-white font-bold rounded-lg hover:bg-brandDark">Request Windows Beta</button>
            </form>
        </div>
        <div class="p-6 bg-slate-50 rounded-2xl text-center">
            <i class="fab fa-apple text-4xl text-slate-800 mb-3"></i>
            <h3 class="font-bold mb-2">macOS</h3>
            <form method="POST" action="{{ route('bconnect.remote.agent.request') }}" class="space-y-3 text-left">
                @csrf
                <input type="hidden" name="os" value="macos">
                <textarea name="message" rows="2" placeholder="Tell us about your use case (optional)" class="w-full p-2 rounded-lg bg-white border border-slate-200 text-sm"></textarea>
                <button type="submit" class="w-full px-4 py-2 bg-brand text-white font-bold rounded-lg hover:bg-brandDark">Request macOS Beta</button>
            </form>
        </div>
    </div>
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-amber-800 text-sm mb-6">
        <i class="fas fa-info-circle mr-1"></i> The browser-based remote view is available now under <strong>Remote &rarr; Request Control</strong>. The desktop agent unlocks full keyboard/mouse OS control and is currently in closed beta.
    </div>
    <p class="text-center text-slate-500 text-sm"><a href="{{ route('bconnect.remote') }}" class="text-brand font-semibold">&larr; Back to Remote Sessions</a></p>
</div>
</body>
</html>
