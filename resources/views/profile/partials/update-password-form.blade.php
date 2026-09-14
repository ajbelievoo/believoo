<form method="post" action="{{ route('password.update') }}" class="space-y-5">
    @csrf
    @method('put')

    <div>
        <label for="update_password_current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-lock text-gray-400 text-sm"></i>
            </div>
            <input type="password" id="update_password_current_password" name="current_password" 
                   class="pl-10 block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm py-2.5 border" 
                   autocomplete="current-password">
        </div>
        <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-1" />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label for="update_password_password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-key text-gray-400 text-sm"></i>
                </div>
                <input type="password" id="update_password_password" name="password" 
                       class="pl-10 block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm py-2.5 border" 
                       autocomplete="new-password">
            </div>
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-1" />
        </div>

        <div>
            <label for="update_password_password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-check-circle text-gray-400 text-sm"></i>
                </div>
                <input type="password" id="update_password_password_confirmation" name="password_confirmation" 
                       class="pl-10 block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm py-2.5 border" 
                       autocomplete="new-password">
            </div>
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-1" />
        </div>
    </div>

    <div class="flex items-center justify-end pt-4">
        <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors duration-200 shadow-sm">
            <i class="fas fa-key mr-2"></i>
            Update Password
        </button>

        @if (session('status') === 'password-updated')
            <span
                x-data="{ show: true }"
                x-show="show"
                x-transition
                x-init="setTimeout(() => show = false, 2000)"
                class="ml-3 text-sm text-green-600 font-medium"
            ><i class="fas fa-check mr-1"></i>Updated</span>
        @endif
    </div>
</form>
