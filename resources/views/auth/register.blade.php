<x-guest-layout>
    <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-slate-900 mb-1">Create Account</h1>
        <p class="text-slate-500 text-sm">Join Believoo today</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <label for="name" class="text-sm font-semibold text-slate-700 block mb-1">Full Name</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}"
                   class="w-full px-4 py-3 rounded-xl bg-white border border-slate-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none transition-all text-sm text-slate-900" required autofocus autocomplete="name"
                   placeholder="John Doe" />
            @error('name')
                <p class="mt-1 text-red-500 text-xs font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="text-sm font-semibold text-slate-700 block mb-1">Email Address</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   class="w-full px-4 py-3 rounded-xl bg-white border border-slate-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none transition-all text-sm text-slate-900" required autocomplete="username"
                   placeholder="you@example.com" />
            @error('email')
                <p class="mt-1 text-red-500 text-xs font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="phone" class="text-sm font-semibold text-slate-700 block mb-1">Phone Number</label>
            <input id="phone" type="tel" name="phone" value="{{ old('phone') }}"
                   class="w-full px-4 py-3 rounded-xl bg-white border border-slate-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none transition-all text-sm text-slate-900" required autocomplete="tel"
                   placeholder="+91 9876543210" />
            @error('phone')
                <p class="mt-1 text-red-500 text-xs font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="text-sm font-semibold text-slate-700 block mb-1">Password</label>
            <input id="password" type="password" name="password"
                   class="w-full px-4 py-3 rounded-xl bg-white border border-slate-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none transition-all text-sm text-slate-900" required autocomplete="new-password"
                   placeholder="••••••••" />
            @error('password')
                <p class="mt-1 text-red-500 text-xs font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="text-sm font-semibold text-slate-700 block mb-1">Confirm Password</label>
            <input id="password_confirmation" type="password" name="password_confirmation"
                   class="w-full px-4 py-3 rounded-xl bg-white border border-slate-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none transition-all text-sm text-slate-900" required autocomplete="new-password"
                   placeholder="••••••••" />
            @error('password_confirmation')
                <p class="mt-1 text-red-500 text-xs font-medium">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="w-full py-3.5 px-6 rounded-xl bg-amber-500 text-white font-bold text-sm hover:bg-amber-600 transition-colors shadow-md shadow-amber-500/20">
            Create Account
        </button>

        @if(config('auth.social_login_enabled', true))
            <div class="relative flex items-center justify-center my-2">
                <div class="flex-grow border-t border-slate-200"></div>
                <span class="flex-shrink mx-4 text-[10px] font-semibold text-slate-500 uppercase tracking-wider">OR</span>
                <div class="flex-grow border-t border-slate-200"></div>
            </div>
            <a href="{{ route('auth.google') }}"
               class="w-full flex items-center justify-center gap-3 px-6 py-3.5 rounded-xl bg-slate-100 border border-slate-200 text-slate-700 font-semibold text-sm hover:bg-slate-200 transition-colors">
                <i class="fab fa-google text-lg"></i>
                Sign up with Google
            </a>
        @endif

        <p class="text-center text-xs text-slate-500">
            Already have an account?
            <a href="{{ route('login') }}" class="text-amber-600 hover:text-amber-700 font-semibold">Sign In</a>
        </p>
    </form>
</x-guest-layout>
