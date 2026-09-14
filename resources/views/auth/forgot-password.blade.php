<x-guest-layout>
    <div class="text-center mb-8">
        <div class="w-14 h-14 rounded-2xl bg-amber-100 flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-key text-2xl text-amber-600"></i>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 mb-1">Reset Password</h1>
        <p class="text-slate-500 text-sm">Enter your email to receive a reset link</p>
    </div>

    <x-auth-session-status class="mb-4 text-green-600 text-sm font-medium text-center" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="text-sm font-semibold text-slate-700 block mb-1">Email Address</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   class="w-full px-4 py-3 rounded-xl bg-white border border-slate-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none transition-all text-sm text-slate-900" required autofocus
                   placeholder="you@example.com" />
            @error('email')
                <p class="mt-1 text-red-500 text-xs font-medium">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="w-full py-3.5 px-6 rounded-xl bg-amber-500 text-white font-bold text-sm hover:bg-amber-600 transition-colors shadow-md shadow-amber-500/20">
            Send Reset Link
        </button>

        <p class="text-center text-xs text-slate-500">
            Remember your password?
            <a href="{{ route('login') }}" class="text-amber-600 hover:text-amber-700 font-semibold">Sign In</a>
        </p>
    </form>
</x-guest-layout>
