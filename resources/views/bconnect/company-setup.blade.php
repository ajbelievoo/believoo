<!DOCTYPE html>
<html lang="en" class="dark">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>B-CONNECT Setup</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="/css/bconnect.css">
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={darkMode:'class',theme:{extend:{colors:{brand:'#00b7ff'}}}};</script>
<style>body{background:var(--bc-bg);color:var(--bc-text);font-family:Inter,ui-sans-serif,system-ui,sans-serif;}</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
<div class="w-full max-w-lg">
    <div class="text-center mb-8">
        <img src="{{ $bconnectBrand['logo'] ?? 'https://believoo.com/images/bconnect-logo.png' }}" class="h-16 w-16 rounded-xl mx-auto mb-4" alt="B-CONNECT">
        <h1 class="text-3xl font-black text-cyan-400 mb-2">Create your workspace</h1>
        <p class="text-slate-400 text-sm">Set up your B-CONNECT company in seconds.</p>
    </div>
    <div class="bc-card p-8">
        @if(session('error'))<div class="mb-4 p-3 rounded-lg bg-red-500/10 text-red-400 border border-red-500/20 text-sm"><i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}</div>@endif
        <form method="POST" action="{{ route('bconnect.company.store') }}" class="space-y-5">@csrf
            <div>
                <label class="block text-sm text-slate-400 mb-1">Company / Workspace Name</label>
                <input type="text" name="company_name" placeholder="e.g. Acme Technologies" required class="bc-input" autofocus>
            </div>
            <div class="p-4 rounded-xl bg-slate-800/50 border border-slate-700 text-sm text-slate-400">
                <i class="fas fa-info-circle text-cyan-400 mr-2"></i>
                Free plan includes 2 members, 100 meetings and 50 tickets. Upgrade anytime from Billing.
            </div>
            <button type="submit" class="bc-btn bc-btn-primary w-full"><i class="fas fa-rocket mr-2"></i>Create Workspace</button>
        </form>
    </div>
    <p class="text-center text-slate-500 text-sm mt-6"><a href="https://believoo.com" class="text-cyan-400 hover:underline">&larr; Back to Believoo</a></p>
</div>
</body>
</html>
