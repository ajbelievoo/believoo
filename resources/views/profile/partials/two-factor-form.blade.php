<section class="space-y-6">
    <header>
        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
            <i class="fas fa-shield-alt text-amber-500"></i>
            Two-Factor Authentication
        </h3>
        <p class="mt-1 text-sm text-gray-500">
            Add an extra layer of security to your account using an authenticator app.
        </p>
    </header>

    <div class="bg-white rounded-xl border border-gray-200 p-4">
        @if (Auth::user()->twoFactorEnabled())
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm font-medium text-green-700 flex items-center gap-2">
                        <i class="fas fa-check-circle"></i>
                        2FA is enabled
                    </p>
                    <p class="text-xs text-gray-500 mt-1">Confirmed at {{ Auth::user()->two_factor_confirmed_at->format('M d, Y H:i') }}</p>
                </div>
                <a href="{{ route('two-factor.recovery') }}" class="text-sm text-amber-600 hover:text-amber-700 font-medium">
                    View Recovery Codes
                </a>
            </div>

            <form method="POST" action="{{ route('two-factor.destroy') }}" class="space-y-4">
                @csrf
                @method('DELETE')
                <div>
                    <label for="two-factor-password" class="block text-sm font-medium text-gray-700">Current Password</label>
                    <input id="two-factor-password" type="password" name="password" required
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 text-sm" />
                    @error('password', 'twoFactor')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Disable 2FA
                </button>
            </form>
        @else
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-600">2FA is currently disabled.</p>
                <a href="{{ route('two-factor.setup') }}" class="inline-flex items-center px-4 py-2 bg-amber-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-700 focus:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Enable 2FA
                </a>
            </div>
        @endif
    </div>
</section>
