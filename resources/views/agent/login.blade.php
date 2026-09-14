<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Login - Believoo Support</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="min-h-screen bg-slate-900 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center mb-4">
                <i class="fas fa-headset text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl font-black text-white">Believoo Support</h1>
            <p class="text-slate-400 text-sm mt-1">Agent Dashboard Login</p>
        </div>

        <div class="bg-white rounded-3xl shadow-2xl p-8">
            @if($errors->any())
                <div class="mb-4 p-3 rounded-xl bg-red-50 text-red-600 text-sm font-medium">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ url(($base ?? '/agent') . '/login') }}" class="space-y-5">
                @csrf
                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                        class="mt-1 w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 focus:border-amber-500 outline-none text-sm">
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Password</label>
                    <input type="password" name="password" required
                        class="mt-1 w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 focus:border-amber-500 outline-none text-sm">
                </div>
                <button type="submit" class="w-full py-3.5 rounded-xl bg-amber-500 text-white font-bold text-sm hover:bg-amber-600 transition-colors">
                    <i class="fas fa-sign-in-alt mr-2"></i>Login to Dashboard
                </button>
            </form>
        </div>
        <p class="text-center text-slate-500 text-xs mt-6">Believoo Support Team Portal</p>
    </div>
</body>
</html>
