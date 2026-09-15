<x-guest-layout>
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-amber-100 text-amber-600 mb-4">
            <i class="fas fa-mobile-alt text-2xl"></i>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 mb-1">Two-Factor Authentication</h1>
        <p class="text-slate-500 text-sm">Enter the 6-digit code from your authenticator app</p>
    </div>

    <form method="POST" action="{{ route('two-factor.verify') }}" class="space-y-5">
        @csrf

        <div class="space-y-2">
            <label for="code" class="text-sm font-semibold text-slate-700 flex items-center gap-2">
                <i class="fas fa-key text-xs text-slate-400"></i>
                Authentication Code
            </label>
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <i class="fas fa-shield-alt text-slate-400 group-focus-within:text-amber-500 transition-colors"></i>
                </div>
                <input id="code" type="text" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6"
                       class="w-full pl-12 pr-4 py-3 rounded-xl bg-white border border-slate-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none transition-all text-sm text-slate-900 tracking-widest font-mono text-center"
                       placeholder="000000" autofocus autocomplete="one-time-code" required />
            </div>
            @error('code')
                <p class="text-red-500 text-xs font-medium flex items-center gap-1">
                    <i class="fas fa-exclamation-circle text-xs"></i>
                    {{ $message }}
                </p>
            @enderror
        </div>

        <button type="submit" class="w-full py-3.5 px-6 rounded-xl bg-amber-500 text-white font-bold text-sm hover:bg-amber-600 transition-colors shadow-md shadow-amber-500/20">
            <span class="flex items-center justify-center gap-2">
                <i class="fas fa-check-circle"></i>
                Verify & Continue
            </span>
        </button>
    </form>

    <div class="mt-6 pt-6 border-t border-slate-100 text-center">
        <p class="text-sm text-slate-500">
            Lost access to your authenticator?
            <a href="{{ route('login') }}" class="font-semibold text-amber-600 hover:text-amber-700 transition-colors ml-1">
                Sign in again
            </a>
        </p>
    </div>
</x-guest-layout>
