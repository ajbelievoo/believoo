<form id="send-verification" method="post" action="{{ route('verification.send') }}">
    @csrf
</form>

<form method="post" action="{{ route('profile.update') }}" class="space-y-5">
    @csrf
    @method('patch')

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-user text-gray-400 text-sm"></i>
                </div>
                <input type="text" id="name" name="name" 
                       class="pl-10 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 border" 
                       :value="old('name', $user->name)" required autocomplete="name">
            </div>
            <x-input-error class="mt-1" :messages="$errors->get('name')" />
        </div>

        <div>
            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-phone text-gray-400 text-sm"></i>
                </div>
                <input type="tel" id="phone" name="phone" 
                       class="pl-10 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 border" 
                       :value="old('phone', $user->phone)" required autocomplete="tel" placeholder="+91 9876543210">
            </div>
            <x-input-error class="mt-1" :messages="$errors->get('phone')" />
        </div>
    </div>

    <div>
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-envelope text-gray-400 text-sm"></i>
            </div>
            <input type="email" id="email" name="email" 
                   class="pl-10 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 border" 
                   :value="old('email', $user->email)" required autocomplete="username">
        </div>
        <x-input-error class="mt-1" :messages="$errors->get('email')" />

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="mt-2">
                <p class="text-sm text-gray-800">
                    {{ __('Your email address is unverified.') }}
                    <button form="send-verification" class="underline text-sm text-blue-600 hover:text-blue-800 font-medium">
                        {{ __('Re-send verification email') }}
                    </button>
                </p>

                @if (session('status') === 'verification-link-sent')
                    <p class="mt-2 font-medium text-sm text-green-600">
                        {{ __('A new verification link has been sent to your email address.') }}
                    </p>
                @endif
            </div>
        @endif
    </div>

    <div class="flex items-center justify-end pt-4">
        <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors duration-200 shadow-sm">
            <i class="fas fa-save mr-2"></i>
            Save Changes
        </button>

        @if (session('status') === 'profile-updated')
            <span
                x-data="{ show: true }"
                x-show="show"
                x-transition
                x-init="setTimeout(() => show = false, 2000)"
                class="ml-3 text-sm text-green-600 font-medium"
            ><i class="fas fa-check mr-1"></i>Saved</span>
        @endif
    </div>
</form>
