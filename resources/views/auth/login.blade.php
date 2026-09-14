<x-guest-layout>
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-amber-100 text-amber-600 mb-4">
            <i class="fas fa-shield-alt text-2xl"></i>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 mb-1">Welcome Back</h1>
        <p class="text-slate-500 text-sm">Sign in to access your client dashboard</p>
    </div>

    <x-auth-session-status class="mb-6 text-green-600 text-sm font-medium text-center" :status="session('status')" />

    <div x-data="{ showPassword: false, loginType: 'email' }">
        <div class="flex mb-6 bg-slate-100 rounded-xl p-1">
            <button type="button"
                    @click="loginType = 'email'"
                    :class="loginType === 'email' ? 'bg-white text-amber-600 font-semibold shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                    class="flex-1 py-2.5 px-4 rounded-lg text-sm transition-all duration-200">
                <i class="fas fa-envelope mr-2"></i>Email
            </button>
            <button type="button"
                    @click="loginType = 'phone'"
                    :class="loginType === 'phone' ? 'bg-white text-amber-600 font-semibold shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                    class="flex-1 py-2.5 px-4 rounded-lg text-sm transition-all duration-200">
                <i class="fas fa-phone mr-2"></i>Mobile
            </button>
        </div>

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf
            @if(request()->has('redirect'))
                <input type="hidden" name="redirect" value="{{ request('redirect') }}">
            @endif

            <div x-show="loginType === 'email'" class="space-y-2">
                <label for="email" class="text-sm font-semibold text-slate-700 flex items-center gap-2">
                    <i class="fas fa-envelope text-xs text-slate-400"></i>
                    Email Address
                </label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fas fa-at text-slate-400 group-focus-within:text-amber-500 transition-colors"></i>
                    </div>
                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                           class="w-full pl-12 pr-4 py-3 rounded-xl bg-white border border-slate-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none transition-all text-sm text-slate-900" autofocus autocomplete="username"
                           placeholder="you@company.com" />
                </div>
                @error('email')
                    <p class="text-red-500 text-xs font-medium flex items-center gap-1">
                        <i class="fas fa-exclamation-circle text-xs"></i>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div x-show="loginType === 'phone'" x-cloak class="space-y-2">
                <label for="phone" class="text-sm font-semibold text-slate-700 flex items-center gap-2">
                    <i class="fas fa-phone text-xs text-slate-400"></i>
                    Mobile Number
                </label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fas fa-mobile-alt text-slate-400 group-focus-within:text-amber-500 transition-colors"></i>
                    </div>
                    <input id="phone" type="tel" name="phone" value="{{ old('phone') }}"
                           class="w-full pl-12 pr-4 py-3 rounded-xl bg-white border border-slate-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none transition-all text-sm text-slate-900"
                           placeholder="+91 9876543210" />
                </div>
                <p class="text-xs text-slate-500">Enter with country code (e.g., +91)</p>
                @error('phone')
                    <p class="text-red-500 text-xs font-medium flex items-center gap-1">
                        <i class="fas fa-exclamation-circle text-xs"></i>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div class="space-y-2">
                <label for="password" class="text-sm font-semibold text-slate-700 flex items-center gap-2">
                    <i class="fas fa-lock text-xs text-slate-400"></i>
                    Password
                </label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fas fa-key text-slate-400 group-focus-within:text-amber-500 transition-colors"></i>
                    </div>
                    <input id="password" :type="showPassword ? 'text' : 'password'" name="password"
                           class="w-full pl-12 pr-12 py-3 rounded-xl bg-white border border-slate-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none transition-all text-sm text-slate-900" required autocomplete="current-password"
                           placeholder="Enter your password" />
                    <button type="button" @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-amber-500 transition-colors">
                        <i class="fas" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
                @error('password')
                    <p class="text-red-500 text-xs font-medium flex items-center gap-1">
                        <i class="fas fa-exclamation-circle text-xs"></i>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div class="flex items-center justify-between">
                <label for="remember_me" class="inline-flex items-center cursor-pointer group">
                    <input id="remember_me" type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-300 text-amber-500 focus:ring-amber-500">
                    <span class="ml-2 text-sm text-slate-600">Remember me</span>
                </label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-sm font-semibold text-amber-600 hover:text-amber-700 transition-colors">
                        Forgot password?
                    </a>
                @endif
            </div>

            <button type="submit" class="w-full py-3.5 px-6 rounded-xl bg-amber-500 text-white font-bold text-sm hover:bg-amber-600 transition-colors shadow-md shadow-amber-500/20">
                <span class="flex items-center justify-center gap-2">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In to Dashboard
                </span>
            </button>

            @php
                $googleEnabled = \App\Models\Setting::getValue('google_login_enabled', '1') == '1';
                $facebookEnabled = \App\Models\Setting::getValue('facebook_login_enabled', '0') == '1';
                $twitterEnabled = \App\Models\Setting::getValue('twitter_login_enabled', '0') == '1';
                $anySocialEnabled = $googleEnabled || $facebookEnabled || $twitterEnabled;
            @endphp

            @if($anySocialEnabled)
                <div class="relative flex items-center justify-center py-4">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-slate-200"></div>
                    </div>
                    <span class="relative px-4 bg-white text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        Or continue with
                    </span>
                </div>

                <div class="space-y-3">
                    @if($googleEnabled)
                        <a href="{{ route('auth.google') }}" class="w-full flex items-center justify-center gap-3 px-6 py-3.5 rounded-xl bg-slate-100 border border-slate-200 text-slate-700 font-semibold text-sm hover:bg-slate-200 transition-colors">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M23.766 12.2764C23.766 11.4607 23.6999 10.6406 23.5588 9.83807H12.24V14.4591H18.7217C18.4528 15.9494 17.5885 17.2678 16.323 18.1056V21.1039H20.19C22.4608 19.0139 23.766 15.9274 23.766 12.2764Z" fill="#4285F4"/>
                                <path d="M12.2401 24.0008C15.4766 24.0008 18.2059 22.9382 20.1945 21.1039L16.3275 18.1055C15.2517 18.8375 13.8627 19.252 12.2445 19.252C9.11388 19.252 6.45946 17.1399 5.50705 14.3003H1.5166V17.3912C3.55371 21.4434 7.7029 24.0008 12.2401 24.0008Z" fill="#34A853"/>
                                <path d="M5.50253 14.3003C4.99987 12.8099 4.99987 11.1961 5.50253 9.70575V6.61481H1.51649C-0.18551 10.0056 -0.18551 14.0004 1.51649 17.3912L5.50253 14.3003Z" fill="#FBBC05"/>
                                <path d="M12.2401 4.74966C13.9509 4.7232 15.6044 5.36697 16.8434 6.54867L20.2695 3.12262C18.1001 1.0855 15.2208 -0.034466 12.2401 0.000808666C7.7029 0.000808666 3.55371 2.55822 1.5166 6.61481L5.50264 9.70575C6.45064 6.86173 9.10947 4.74966 12.2401 4.74966Z" fill="#EA4335"/>
                            </svg>
                            Continue with Google
                        </a>
                    @endif

                    @if($facebookEnabled)
                        <a href="{{ route('auth.facebook') }}" class="w-full flex items-center justify-center gap-3 px-6 py-3.5 rounded-xl bg-slate-100 border border-slate-200 text-slate-700 font-semibold text-sm hover:bg-slate-200 transition-colors">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M24 12.073C24 5.404 18.627 0 12 0S0 5.404 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047v-2.66c0-3.026 1.79-4.697 4.533-4.697 1.312 0 2.686.236 2.686.236v2.97h-1.513c-1.491 0-1.956.931-1.956 1.886v2.264h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z" fill="#1877F2"/>
                            </svg>
                            Continue with Facebook
                        </a>
                    @endif

                    @if($twitterEnabled)
                        <a href="{{ route('auth.twitter') }}" class="w-full flex items-center justify-center gap-3 px-6 py-3.5 rounded-xl bg-slate-100 border border-slate-200 text-slate-700 font-semibold text-sm hover:bg-slate-200 transition-colors">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                            </svg>
                            Continue with Twitter
                        </a>
                    @endif
                </div>
            @endif
        </form>
    </div>

    <div class="mt-6 pt-6 border-t border-slate-100 text-center">
        <p class="text-sm text-slate-500">
            New to Believoo?
            <a href="{{ route('register') }}" class="font-semibold text-amber-600 hover:text-amber-700 transition-colors ml-1">
                Create an account
                <i class="fas fa-arrow-right text-xs ml-1"></i>
            </a>
        </p>
    </div>

    <div class="mt-6 flex items-center justify-center gap-2 text-xs text-slate-400">
        <i class="fas fa-lock"></i>
        <span>Secured with 256-bit encryption</span>
    </div>
</x-guest-layout>
