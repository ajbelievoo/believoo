<x-guest-layout>
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-amber-100 text-amber-600 mb-4">
            <i class="fas fa-qrcode text-2xl"></i>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 mb-1">Set Up Two-Factor Authentication</h1>
        <p class="text-slate-500 text-sm">Scan the QR code with your authenticator app and enter the code</p>
    </div>

    @if (session('status'))
        <div class="mb-6 p-4 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">
            {{ session('status') }}
        </div>
    @endif

    <div class="space-y-6">
        <div class="bg-white p-6 rounded-2xl border border-slate-200 text-center">
            <div class="flex justify-center mb-4">
                {!! $qrSvg !!}
            </div>
            <p class="text-xs text-slate-500 mb-2">If you can't scan the QR code, enter this key manually:</p>
            <code class="block p-3 bg-slate-100 rounded-lg text-xs font-mono text-slate-700 break-all select-all">{{ $secret }}</code>
        </div>

        <form method="POST" action="{{ route('two-factor.enable') }}" class="space-y-5">
            @csrf

            <div class="space-y-2">
                <label for="code" class="text-sm font-semibold text-slate-700 flex items-center gap-2">
                    <i class="fas fa-key text-xs text-slate-400"></i>
                    Verification Code
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
                    Enable 2FA
                </span>
            </button>
        </form>
    </div>

    <div class="mt-4 text-center">
        <a href="{{ route('profile.edit') }}" class="text-sm text-slate-500 hover:text-amber-600 transition-colors">
            <i class="fas fa-arrow-left mr-1"></i>
            Back to profile
        </a>
    </div>
</x-guest-layout>
