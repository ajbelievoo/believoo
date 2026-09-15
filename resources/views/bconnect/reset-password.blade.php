<!DOCTYPE html>
<html lang="en" class="dark">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Reset Password | {{ $bconnectBrand['title'] }}</title>
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
        <h1 class="text-2xl font-black text-white">Reset password</h1>
        <p class="text-slate-400 text-sm mt-1">Create a new password for your B-CONNECT workspace</p>
    </div>
    @if(session('status'))<div class="mb-4 p-3 rounded-lg bg-green-500/10 text-green-400 text-sm border border-green-500/20"><i class="fas fa-check-circle mr-2"></i>{{ session('status') }}</div>@endif
    @if($errors->any())<div class="mb-4 p-3 rounded-lg bg-red-500/10 text-red-400 text-sm border border-red-500/20"><i class="fas fa-exclamation-circle mr-2"></i>{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('bconnect.password.update') }}" class="space-y-4">@csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">
        <input type="password" name="password" placeholder="New password" required class="bc-input" autocomplete="new-password">
        <input type="password" name="password_confirmation" placeholder="Confirm new password" required class="bc-input" autocomplete="new-password">
        <button type="submit" class="bc-btn bc-btn-primary w-full"><i class="fas fa-lock"></i>Reset Password</button>
    </form>
    <p class="text-center text-slate-500 text-xs mt-4"><a href="https://believoo.com" class="hover:text-cyan-400">&larr; Back to Believoo</a></p>
</div>
</body>
</html>
