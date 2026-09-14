<!DOCTYPE html>
<html lang="en" class="dark">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>B-CONNECT Setup</title>
<script src="https://cdn.tailwindcss.com"></script><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>body{background:#0f172a;color:#e2e8f0;}</style>
</head>
<body class="min-h-screen flex items-center justify-center">
<div class="w-full max-w-md p-8 bg-slate-900 rounded-2xl border border-slate-700 shadow-2xl">
    <h1 class="text-3xl font-black text-cyan-400 mb-2">Create Company</h1>
    <p class="text-slate-400 text-sm mb-6">Setup your B-CONNECT workspace</p>
    @if(session('error'))<div class="mb-4 p-3 rounded bg-red-500/10 text-red-400 text-sm">{{ session('error') }}</div>@endif
    <form method="POST" action="{{ route('bconnect.company.store') }}" class="space-y-4">@csrf
        <input type="text" name="company_name" placeholder="Company Name" required class="w-full p-3 rounded-lg bg-slate-800 border border-slate-600 text-white">
        <button type="submit" class="w-full p-3 bg-cyan-500 hover:bg-cyan-400 text-slate-900 font-bold rounded-lg">Create Workspace</button>
    </form>
</div>
</body>
</html>
