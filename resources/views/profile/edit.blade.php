<x-client-layout>
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Profile Settings</h1>
        <p class="mt-1 text-sm text-gray-500">Manage your account settings and preferences</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column - Profile Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Profile Information Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-user text-blue-600"></i>
                        </div>
                        <div class="ml-4">
                            <h2 class="text-lg font-semibold text-gray-900">Profile Information</h2>
                            <p class="text-sm text-gray-500">Update your personal details</p>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <!-- Password Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center">
                            <i class="fas fa-lock text-green-600"></i>
                        </div>
                        <div class="ml-4">
                            <h2 class="text-lg font-semibold text-gray-900">Security</h2>
                            <p class="text-sm text-gray-500">Update your password</p>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <!-- Delete Account Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center">
                            <i class="fas fa-trash-alt text-red-600"></i>
                        </div>
                        <div class="ml-4">
                            <h2 class="text-lg font-semibold text-gray-900">Danger Zone</h2>
                            <p class="text-sm text-gray-500">Permanently delete your account</p>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>

        <!-- Right Column - Quick Info -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-8">
                <div class="text-center">
                    <div class="w-24 h-24 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 mx-auto flex items-center justify-center text-white text-3xl font-bold shadow-lg">
                        {{ substr(Auth::user()->name, 0, 1) }}
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900">{{ Auth::user()->name }}</h3>
                    <p class="text-sm text-gray-500">{{ Auth::user()->email }}</p>
                    
                    @if(Auth::user()->phone)
                        <p class="text-sm text-gray-500 mt-1">
                            <i class="fas fa-phone mr-1 text-gray-400"></i>
                            {{ Auth::user()->phone }}
                        </p>
                    @endif
                </div>

                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h4 class="text-sm font-medium text-gray-900 mb-3">Account Status</h4>
                    <div class="flex items-center">
                        <span class="w-2 h-2 rounded-full bg-green-500 mr-2"></span>
                        <span class="text-sm text-gray-600">Active</span>
                    </div>
                    <div class="mt-2 text-xs text-gray-500">
                        Member since {{ Auth::user()->created_at->format('M Y') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-client-layout>
