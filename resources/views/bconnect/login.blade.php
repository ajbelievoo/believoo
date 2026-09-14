<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Login | {{ $bconnectBrand['title'] }}</title>
<link rel="icon" type="image/png" href="{{ $bconnectBrand['favicon'] }}">
<meta name="description" content="{{ $bconnectBrand['description'] }}">
<link rel="preconnect" href="https://fonts.bunny.net"><link href="https://fonts.bunny.net/css?family=figtree:400,600,800&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script><script>tailwind.config={theme:{extend:{colors:{brand:'#00B7FF'}}}}</script><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>html,body{font-family:Figtree,Inter,sans-serif;background:#f8fafc;}</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
<div class="w-full max-w-md p-8 bg-white rounded-2xl border border-slate-200 shadow-2xl">
    <div class="text-center mb-6">
        <h1 class="text-3xl font-extrabold text-slate-900">Welcome back</h1>
        <p class="text-slate-500 text-sm mt-1">Login to your B-CONNECT workspace</p>
    </div>
    @if(session('error'))<div class="mb-4 p-3 rounded bg-red-50 text-red-500 text-sm">{{ session('error') }}</div>@endif
    @if(session('info'))<div class="mb-4 p-3 rounded bg-blue-50 text-blue-500 text-sm">{{ session('info') }}</div>@endif
    <form method="POST" action="{{ route('bconnect.login') }}" class="space-y-4">@csrf
        <input type="email" name="email" placeholder="Email" required class="w-full p-3 rounded-lg bg-slate-50 border border-slate-200 text-slate-900 focus:border-brand outline-none">
        <input type="password" name="password" placeholder="Password" required class="w-full p-3 rounded-lg bg-slate-50 border border-slate-200 text-slate-900 focus:border-brand outline-none">
        <div class="text-right">
            <a href="{{ route('bconnect.forgot-password') }}" class="text-sm text-brand hover:underline">Forgot password?</a>
        </div>
        <button type="submit" class="w-full p-3 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-lg transition">Login</button>
    </form>
    <div class="relative my-6 text-center">
        <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-slate-200"></div></div>
        <span class="relative bg-white px-2 text-xs text-slate-400">or</span>
    </div>
    <a href="{{ route('bconnect.google') }}" class="w-full flex items-center justify-center gap-2 p-3 border border-slate-200 rounded-lg font-semibold text-slate-700 hover:bg-slate-50 transition">
        <img src="https://www.svgrepo.com/show/475656/google-color.svg" class="h-5 w-5"> Continue with Google
    </a>
    <p class="text-center text-slate-500 text-sm mt-6">Don't have an account? <a href="{{ route('bconnect.register') }}" class="text-brand font-semibold hover:underline">Register</a></p>
    <p class="text-center text-slate-400 text-xs mt-4"><a href="https://believoo.com" class="hover:text-brand">&larr; Back to Believoo</a></p>
</div>
</body>
</html>
