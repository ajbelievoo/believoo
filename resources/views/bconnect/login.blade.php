<!DOCTYPE html>
<html lang="en" class="dark">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Login | {{ $bconnectBrand['title'] }}</title>
<link rel="icon" type="image/png" href="{{ $bconnectBrand['favicon'] }}">
<meta name="description" content="{{ $bconnectBrand['description'] }}">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="/css/bconnect.css">
<script>tailwind.config={darkMode:'class',theme:{extend:{colors:{brand:'{{ $bconnectBrand['brand_color'] }}'}}}}</script>
<style>body{background:radial-gradient(circle at top right, rgba({{ $bconnectBrand['brand_rgb'] }},0.08), transparent 40%), #0b1220;}</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
<div class="w-full max-w-md p-8 bg-[#0f172a] rounded-2xl border border-[var(--bc-border)] shadow-2xl">
    <div class="text-center mb-6">
        <img src="{{ $bconnectBrand['logo'] }}" class="h-16 w-auto max-w-[220px] mx-auto mb-4 object-contain" alt="Bmydesk">
        <h1 class="text-2xl font-black text-white">Welcome back</h1>
        <p class="text-slate-400 text-sm mt-1">Login to your Bmydesk workspace</p>
    </div>
    @if(session('error'))<div class="mb-4 p-3 rounded-lg bg-red-500/10 text-red-400 text-sm border border-red-500/20"><i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}</div>@endif
    @if(session('info'))<div class="mb-4 p-3 rounded-lg bg-cyan-500/10 text-cyan-400 text-sm border border-cyan-500/20"><i class="fas fa-info-circle mr-2"></i>{{ session('info') }}</div>@endif
    <form method="POST" action="{{ route('bconnect.login') }}" class="space-y-4">@csrf
        <input type="email" name="email" placeholder="Email" required class="bc-input" autocomplete="email">
        <input type="password" name="password" placeholder="Password" required class="bc-input" autocomplete="current-password">
        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm text-slate-400 cursor-pointer">
                <input type="checkbox" name="remember" value="1" checked class="accent-rose-500">Keep me signed in
            </label>
            <a href="{{ route('bconnect.forgot-password') }}" class="text-sm text-cyan-400 hover:underline">Forgot password?</a>
        </div>
        <button type="submit" class="bc-btn bc-btn-primary w-full"><i class="fas fa-sign-in-alt"></i>Login</button>
    </form>
    <div class="relative my-6 text-center">
        <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-[var(--bc-border)]"></div></div>
        <span class="relative bg-[#0f172a] px-2 text-xs text-slate-500">or</span>
    </div>
    <a href="{{ route('bconnect.google') }}" class="w-full flex items-center justify-center gap-2 p-3 border border-[var(--bc-border)] rounded-lg font-semibold text-slate-200 hover:bg-slate-800/50 transition">
        <img src="https://www.svgrepo.com/show/475656/google-color.svg" class="h-5 w-5"> Continue with Google
    </a>
    <p class="text-center text-slate-400 text-sm mt-6">Don't have an account? <a href="{{ route('bconnect.register') }}" class="text-cyan-400 font-semibold hover:underline">Register</a></p>
    <p class="text-center text-slate-500 text-xs mt-4"><a href="https://believoo.com" class="hover:text-cyan-400">&larr; Back to Believoo</a></p>
</div>
</body>
</html>
