<!DOCTYPE html>
<html lang="en" class="dark">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Register | {{ $bconnectBrand['title'] }}</title>
<link rel="icon" type="image/png" href="{{ $bconnectBrand['favicon'] }}">
<meta name="description" content="{{ $bconnectBrand['description'] }}">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="/css/bconnect.css">
<script>tailwind.config={darkMode:'class',theme:{extend:{colors:{brand:'#00B7FF'}}}}</script>
<style>body{background:radial-gradient(circle at top right, rgba(0,183,255,0.08), transparent 40%), #0b1220;}</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
<div class="w-full max-w-md p-8 bg-[#0f172a] rounded-2xl border border-[var(--bc-border)] shadow-2xl">
    <div class="text-center mb-6">
        <img src="{{ $bconnectBrand['logo'] }}" class="h-14 w-14 rounded-xl mx-auto mb-4" alt="B-CONNECT">
        <h1 class="text-2xl font-black text-white">Create workspace</h1>
        <p class="text-slate-400 text-sm mt-1">Start your B-CONNECT journey</p>
    </div>
    @if(session('error'))<div class="mb-4 p-3 rounded-lg bg-red-500/10 text-red-400 text-sm border border-red-500/20"><i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}</div>@endif
    <form method="POST" action="{{ route('bconnect.register') }}" class="space-y-4">@csrf
        <input type="text" name="name" placeholder="Full Name" required class="bc-input" autocomplete="name">
        <input type="email" name="email" placeholder="Email" required class="bc-input" autocomplete="email">
        <input type="password" name="password" placeholder="Password" required class="bc-input" autocomplete="new-password">
        <input type="text" name="company_name" placeholder="Company Name" required class="bc-input">
        <button type="submit" class="bc-btn bc-btn-primary w-full"><i class="fas fa-rocket"></i>Create Workspace</button>
    </form>
    <div class="relative my-6 text-center">
        <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-[var(--bc-border)]"></div></div>
        <span class="relative bg-[#0f172a] px-2 text-xs text-slate-500">or</span>
    </div>
    <a href="{{ route('bconnect.google') }}" class="w-full flex items-center justify-center gap-2 p-3 border border-[var(--bc-border)] rounded-lg font-semibold text-slate-200 hover:bg-slate-800/50 transition">
        <img src="https://www.svgrepo.com/show/475656/google-color.svg" class="h-5 w-5"> Continue with Google
    </a>
    <p class="text-center text-slate-400 text-sm mt-6">Already have an account? <a href="{{ route('bconnect.login') }}" class="text-cyan-400 font-semibold hover:underline">Login</a></p>
    <p class="text-center text-slate-500 text-xs mt-4"><a href="https://believoo.com" class="hover:text-cyan-400">&larr; Back to Believoo</a></p>
</div>
</body>
</html>
