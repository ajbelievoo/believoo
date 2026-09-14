<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Reset Password | {{ $bconnectBrand['title'] }}</title>
<link rel="icon" type="image/png" href="{{ $bconnectBrand['favicon'] }}">
<meta name="description" content="{{ $bconnectBrand['description'] }}">
<link rel="preconnect" href="https://fonts.bunny.net"><link href="https://fonts.bunny.net/css?family=figtree:400,600,800&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script><script>tailwind.config={theme:{extend:{colors:{brand:'#00B7FF'}}}}</script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>html,body{font-family:Figtree,Inter,sans-serif;background:#f8fafc;}</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
<div class="w-full max-w-md p-8 bg-white rounded-2xl border border-slate-200 shadow-2xl">
    <div class="text-center mb-6">
        <h1 class="text-3xl font-extrabold text-slate-900">Reset password</h1>
        <p class="text-slate-500 text-sm mt-1">Create a new password for your B-CONNECT workspace</p>
    </div>
    @if(session('status'))<div class="mb-4 p-3 rounded bg-green-50 text-green-600 text-sm">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="mb-4 p-3 rounded bg-red-50 text-red-500 text-sm">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('bconnect.password.update') }}" class="space-y-4">@csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">
        <input type="password" name="password" placeholder="New password" required class="w-full p-3 rounded-lg bg-slate-50 border border-slate-200 text-slate-900 focus:border-brand outline-none">
        <input type="password" name="password_confirmation" placeholder="Confirm new password" required class="w-full p-3 rounded-lg bg-slate-50 border border-slate-200 text-slate-900 focus:border-brand outline-none">
        <button type="submit" class="w-full p-3 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-lg transition">Reset Password</button>
    </form>
    <p class="text-center text-slate-400 text-xs mt-4"><a href="https://believoo.com" class="hover:text-brand">&larr; Back to Believoo</a></p>
</div>
</body>
</html>
