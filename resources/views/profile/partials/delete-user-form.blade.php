<div class="flex items-start justify-between">
    <div class="flex-1">
        <p class="text-sm text-gray-600">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>
    </div>

    <button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        class="ml-4 inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors duration-200 shadow-sm"
    >
        <i class="fas fa-trash-alt mr-2"></i>
        {{ __('Delete Account') }}
    </button>
</div>

<x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
    <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
        @csrf
        @method('delete')

        <div class="flex items-center mb-4">
            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center mr-3">
                <i class="fas fa-exclamation-triangle text-red-600"></i>
            </div>
            <h2 class="text-lg font-semibold text-gray-900">
                {{ __('Delete Account') }}
            </h2>
        </div>

        <p class="text-sm text-gray-600 mb-6">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
        </p>

        <div class="mb-6">
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Password') }}</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-lock text-gray-400 text-sm"></i>
                </div>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="pl-10 block w-full rounded-lg border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm py-2.5 border"
                    placeholder="{{ __('Enter your password') }}"
                />
            </div>
            <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-1" />
        </div>

        <div class="flex justify-end gap-3">
            <button type="button" x-on:click="$dispatch('close')" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors">
                {{ __('Cancel') }}
            </button>

            <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
                <i class="fas fa-trash-alt mr-2"></i>
                {{ __('Delete Account') }}
            </button>
        </div>
    </form>
</x-modal>
