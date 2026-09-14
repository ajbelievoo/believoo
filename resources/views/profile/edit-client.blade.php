<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>My Profile - {{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-[#0a0a0a] text-gray-200 selection:bg-electric-blue selection:text-dark">
        <div class="min-h-screen relative">
            <!-- Navigation Bar -->
            <nav class="fixed top-0 left-0 right-0 z-50 bg-[#0a0a0a]/80 backdrop-blur-xl border-b border-white/5">
                <div class="max-w-7xl mx-auto px-6 py-4">
                    <div class="flex items-center justify-between">
                        <a href="{{ route('client.dashboard') }}" class="text-2xl font-black text-white uppercase tracking-tighter">
                            Client <span class="text-electric-blue">Portal</span>
                        </a>
                        <div class="flex items-center space-x-4">
                            <a href="{{ route('client.dashboard') }}" class="px-4 py-2 text-sm font-bold text-gray-400 hover:text-white transition-all">
                                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <div class="pt-32 pb-20 px-6">
                <div class="max-w-4xl mx-auto">
                    <!-- Header -->
                    <div class="mb-10">
                        <h1 class="text-4xl font-black text-white uppercase tracking-tighter mb-2">
                            My <span class="text-electric-blue">Profile</span>
                        </h1>
                        <p class="text-gray-400">Manage your account settings and personal information.</p>
                    </div>

                    <!-- Profile Information -->
                    <div class="glass rounded-3xl border border-white/10 overflow-hidden mb-6">
                        <div class="p-8 border-b border-white/5 bg-white/5">
                            <h2 class="text-lg font-black text-white uppercase tracking-wider">
                                <i class="fas fa-user-circle mr-2 text-electric-blue"></i>Profile Information
                            </h2>
                            <p class="text-sm text-gray-400 mt-1">Update your account's profile information, email address, and phone number.</p>
                        </div>
                        <div class="p-8">
                            <form method="post" action="{{ route('profile.update') }}" class="space-y-6">
                                @csrf
                                @method('patch')

                                <div>
                                    <label for="name" class="block text-sm font-bold text-gray-300 mb-2">Full Name</label>
                                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autocomplete="name" 
                                        class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-500 focus:border-electric-blue focus:outline-none transition-all">
                                    @error('name')
                                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="email" class="block text-sm font-bold text-gray-300 mb-2">Email Address</label>
                                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="username"
                                        class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-500 focus:border-electric-blue focus:outline-none transition-all">
                                    @error('email')
                                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                                        <div class="mt-2 p-4 bg-yellow-500/10 border border-yellow-500/20 rounded-xl">
                                            <p class="text-sm text-yellow-400">
                                                <i class="fas fa-exclamation-triangle mr-2"></i>Your email address is unverified.
                                                <button form="send-verification" class="underline text-yellow-400 hover:text-yellow-300 ml-1">Click here to re-send the verification email.</button>
                                            </p>
                                        </div>
                                    @endif
                                </div>

                                <div>
                                    <label for="phone" class="block text-sm font-bold text-gray-300 mb-2">
                                        <i class="fas fa-phone mr-2 text-electric-blue"></i>Phone Number <span class="text-red-400">*</span>
                                    </label>
                                    <input id="phone" name="phone" type="tel" value="{{ old('phone', $user->phone) }}" required autocomplete="tel" placeholder="+91 9876543210"
                                        class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-500 focus:border-electric-blue focus:outline-none transition-all">
                                    @error('phone')
                                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                    <p class="text-xs text-gray-500 mt-2">Phone number is required for account security and notifications.</p>
                                </div>

                                <div>
                                    <label for="locale" class="block text-sm font-bold text-gray-300 mb-2">
                                        <i class="fas fa-language mr-2 text-electric-blue"></i>Preferred Language
                                    </label>
                                    <select id="locale" name="locale" class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white focus:border-electric-blue focus:outline-none transition-all">
                                        <option value="en" {{ old('locale', $user->locale) == 'en' ? 'selected' : '' }}>English</option>
                                        <option value="hi" {{ old('locale', $user->locale) == 'hi' ? 'selected' : '' }}>Hindi</option>
                                    </select>
                                    @error('locale')
                                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="flex items-center gap-4 pt-4">
                                    <button type="submit" class="px-8 py-3 rounded-xl bg-electric-blue text-dark font-black uppercase tracking-wider text-sm hover:scale-105 transition-all">
                                        <i class="fas fa-save mr-2"></i>Save Changes
                                    </button>
                                    @if (session('status') === 'profile-updated')
                                        <span class="text-green-400 text-sm font-bold"><i class="fas fa-check-circle mr-1"></i>Saved successfully!</span>
                                    @endif
                                </div>
                            </form>

                            <form id="send-verification" method="post" action="{{ route('verification.send') }}" class="hidden">
                                @csrf
                            </form>
                        </div>
                    </div>

                    <!-- Update Password -->
                    <div class="glass rounded-3xl border border-white/10 overflow-hidden mb-6">
                        <div class="p-8 border-b border-white/5 bg-white/5">
                            <h2 class="text-lg font-black text-white uppercase tracking-wider">
                                <i class="fas fa-lock mr-2 text-electric-violet"></i>Update Password
                            </h2>
                            <p class="text-sm text-gray-400 mt-1">Ensure your account is using a long, random password to stay secure.</p>
                        </div>
                        <div class="p-8">
                            <form method="post" action="{{ route('password.update') }}" class="space-y-6">
                                @csrf
                                @method('put')

                                <div>
                                    <label for="current_password" class="block text-sm font-bold text-gray-300 mb-2">Current Password</label>
                                    <input id="current_password" name="current_password" type="password" autocomplete="current-password"
                                        class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-500 focus:border-electric-blue focus:outline-none transition-all">
                                    @error('current_password', 'updatePassword')
                                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="password" class="block text-sm font-bold text-gray-300 mb-2">New Password</label>
                                    <input id="password" name="password" type="password" autocomplete="new-password"
                                        class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-500 focus:border-electric-blue focus:outline-none transition-all">
                                    @error('password', 'updatePassword')
                                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="password_confirmation" class="block text-sm font-bold text-gray-300 mb-2">Confirm New Password</label>
                                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                                        class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-500 focus:border-electric-blue focus:outline-none transition-all">
                                    @error('password_confirmation', 'updatePassword')
                                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="flex items-center gap-4 pt-4">
                                    <button type="submit" class="px-8 py-3 rounded-xl bg-electric-violet text-white font-black uppercase tracking-wider text-sm hover:scale-105 transition-all">
                                        <i class="fas fa-key mr-2"></i>Update Password
                                    </button>
                                    @if (session('status') === 'password-updated')
                                        <span class="text-green-400 text-sm font-bold"><i class="fas fa-check-circle mr-1"></i>Password updated!</span>
                                    @endif
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Delete Account -->
                    <div class="glass rounded-3xl border border-red-500/20 overflow-hidden">
                        <div class="p-8 border-b border-red-500/10 bg-red-500/5">
                            <h2 class="text-lg font-black text-red-400 uppercase tracking-wider">
                                <i class="fas fa-trash-alt mr-2"></i>Delete Account
                            </h2>
                            <p class="text-sm text-gray-400 mt-1">Once your account is deleted, all of its resources and data will be permanently deleted.</p>
                        </div>
                        <div class="p-8">
                            <button type="button" x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')" 
                                class="px-8 py-3 rounded-xl bg-red-500/20 text-red-400 border border-red-500/30 font-black uppercase tracking-wider text-sm hover:bg-red-500/30 transition-all">
                                <i class="fas fa-exclamation-triangle mr-2"></i>Delete Account
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delete Account Modal -->
            <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
                <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
                    @csrf
                    @method('delete')

                    <h2 class="text-lg font-black text-red-400 uppercase tracking-wider mb-4">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Are you sure?
                    </h2>

                    <p class="text-sm text-gray-400 mb-6">
                        Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.
                    </p>

                    <div class="mb-6">
                        <label for="password" class="block text-sm font-bold text-gray-300 mb-2">Password</label>
                        <input id="password" name="password" type="password" placeholder="Enter your password"
                            class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-500 focus:border-red-400 focus:outline-none transition-all">
                        @error('password', 'userDeletion')
                            <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end gap-4">
                        <button type="button" x-on:click="$dispatch('close')" 
                            class="px-6 py-3 rounded-xl bg-white/5 text-gray-300 font-bold text-sm hover:bg-white/10 transition-all">
                            Cancel
                        </button>
                        <button type="submit" class="px-6 py-3 rounded-xl bg-red-500 text-white font-black uppercase tracking-wider text-sm hover:bg-red-600 transition-all">
                            <i class="fas fa-trash-alt mr-2"></i>Delete Account
                        </button>
                    </div>
                </form>
            </x-modal>
        </div>
    </body>
</html>
