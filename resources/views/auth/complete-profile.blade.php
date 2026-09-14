<x-guest-layout>
    <div class="mb-6 text-center">
        <h2 class="text-2xl font-black text-white uppercase tracking-tight mb-2">
            Complete Your Profile
        </h2>
        <p class="text-gray-400 text-sm">
            Please provide your phone number to continue to the dashboard.
        </p>
    </div>

    <form method="POST" action="{{ route('profile.complete.store') }}">
        @csrf

        <!-- Name (Read-only or editable based on social login) -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email (Read-only) -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full bg-white/5 text-gray-400" type="email" :value="$user->email" disabled />
            <input type="hidden" name="email" value="{{ $user->email }}">
        </div>

        <!-- Phone Number (Required) -->
        <div class="mt-4">
            <x-input-label for="phone" :value="__('Phone Number')" />
            <x-text-input id="phone" class="block mt-1 w-full" type="tel" name="phone" :value="old('phone', $user->phone)" required autocomplete="tel" placeholder="+91 9876543210" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            <p class="text-[10px] text-gray-500 mt-1">Phone number is required to access the dashboard.</p>
        </div>

        <div class="mt-4">
            <x-input-label for="locale" :value="__('Preferred Language')" />
            <select id="locale" name="locale" class="block mt-1 w-full rounded-md border-gray-300 bg-gray-900 text-white">
                <option value="en" {{ old('locale', $user->locale) == 'en' ? 'selected' : '' }}>English</option>
                <option value="hi" {{ old('locale', $user->locale) == 'hi' ? 'selected' : '' }}>Hindi</option>
            </select>
            <x-input-error :messages="$errors->get('locale')" class="mt-2" />
        </div>

        <div class="flex flex-col space-y-4 mt-8">
            <x-primary-button class="w-full justify-center">
                {{ __('Complete Profile & Continue') }}
            </x-primary-button>
        </div>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="block mt-4">
        @csrf
        <button type="submit" class="w-full text-center text-[10px] font-black text-gray-500 hover:text-red-400 transition-colors uppercase tracking-[0.2em]">
            {{ __('Cancel & Log Out') }}
        </button>
    </form>
</x-guest-layout>
