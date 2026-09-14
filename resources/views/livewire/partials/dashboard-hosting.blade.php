{{-- Hosting Tab --}}
@if($selectedHosting)
    {{-- OVH-Style Hosting Details View --}}
    <div class="animate-in fade-in slide-in-from-bottom-4 duration-500" x-data="{ showVnc: false }">
        
        {{-- Loading Overlay --}}
        @if($isLoadingServer)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
                <div class="flex flex-col items-center">
                    <div class="w-12 h-12 border-4 border-electric-blue/30 border-t-electric-blue rounded-full animate-spin"></div>
                    <p class="mt-4 text-sm text-gray-400">Loading server details...</p>
                </div>
            </div>
        @endif

        {{-- Server Action Message --}}
        @if($serverMessage)
            @php
                // Keep password reset message persistent (don't auto-dismiss)
                $isPersistent = str_contains($serverMessage, 'password') || str_contains($serverMessage, 'Password');
            @endphp
            <div class="mb-4 p-4 rounded-xl {{ $serverMessageType === 'success' ? 'bg-green-500/20 border border-green-500/30 text-green-400' : ($serverMessageType === 'error' ? 'bg-red-500/20 border border-red-500/30 text-red-400' : 'bg-blue-500/20 border border-blue-500/30 text-blue-400') }} flex items-center justify-between"
                 @if(!$isPersistent) x-init="setTimeout(() => $wire.clearServerMessage(), 5000)" @endif>
                <span class="text-sm font-medium"><i class="fas {{ $serverMessageType === 'success' ? 'fa-check-circle' : ($serverMessageType === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle') }} mr-2"></i>{{ $serverMessage }}</span>
                <button wire:click="clearServerMessage" class="ml-4 text-gray-400 hover:text-white flex-shrink-0" aria-label="Close message"><i class="fas fa-times"></i></button>
            </div>
        @endif

        {{-- Header with Back Button & Title --}}
        <div class="mb-6">
            <button wire:click="closeHostingView" class="mb-4 text-[10px] font-black text-electric-blue uppercase tracking-widest flex items-center hover:translate-x-[-4px] transition-all">
                <i class="fas fa-arrow-left mr-2"></i> Back to Hosting
            </button>
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-3xl font-black text-white uppercase tracking-tight">{{ $selectedHosting->server_hostname ?? $selectedHosting->primary_domain ?? $selectedHosting->plan_name }}</h2>
                    <p class="text-gray-400 text-sm mt-1">{{ $selectedHosting->plan_name }} • {{ $serverDetails['location'] ?? $selectedHosting->datacenter_location ?? 'Unknown Location' }}</p>
                </div>
                <div class="flex items-center gap-3">
                    @php
                        $serverState = $serverDetails['state'] ?? 'unknown';
                        $stateClass = match($serverState) {
                            'running', 'online' => 'bg-green-500/20 text-green-500',
                            'stopped', 'offline' => 'bg-yellow-500/20 text-yellow-500',
                            default => 'bg-gray-500/20 text-gray-500',
                        };
                        $stateIcon = match($serverState) {
                            'running', 'online' => 'fa-play',
                            'stopped', 'offline' => 'fa-stop',
                            default => 'fa-question',
                        };
                    @endphp
                    <span class="px-4 py-2 rounded-full text-xs font-black uppercase tracking-widest {{ $stateClass }}">
                        <i class="fas {{ $stateIcon }} text-[8px] mr-1"></i>{{ $serverState }}
                    </span>
                    <span class="px-4 py-2 rounded-full text-xs font-black uppercase tracking-widest {{ $selectedHosting->getStatusBadgeClass() }}">
                        <i class="fas fa-circle text-[8px] mr-1"></i>{{ $selectedHosting->status }}
                    </span>
                    @if($selectedHosting->isExpiringSoon())
                        <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-widest bg-red-500/20 text-red-500">
                            {{ $selectedHosting->getDaysRemaining() }} days left
                        </span>
                    @endif
                    <button wire:click="refreshServerStatus" wire:loading.attr="disabled" class="p-2 rounded-lg bg-white/5 text-gray-400 hover:text-electric-blue hover:bg-electric-blue/10 transition-all" title="Refresh Status">
                        <i class="fas fa-sync-alt {{ $isLoadingServer ? 'animate-spin' : '' }}"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- Server Power Controls --}}
        <div class="mb-6 p-4 bg-white/5 rounded-2xl border border-white/10">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-electric-blue/20 to-electric-violet/20 flex items-center justify-center">
                        <i class="fas fa-power-off text-electric-blue"></i>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-white">Power Controls</p>
                        <p class="text-xs text-gray-400">Manage server power state</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if($serverActions['can_start'] ?? false)
                        <button wire:click="confirmServerAction('start', 'Start Server', 'This will start your server. Continue?')" 
                                wire:loading.attr="disabled"
                                class="px-4 py-2 rounded-xl bg-green-500/20 text-green-500 border border-green-500/30 font-black uppercase tracking-wider text-xs hover:bg-green-500 hover:text-white transition-all flex items-center gap-2">
                            <i class="fas fa-play"></i> Start
                        </button>
                    @endif
                    @if($serverActions['can_stop'] ?? false)
                        <button wire:click="confirmServerAction('stop', 'Stop Server', 'This will gracefully shut down your server. Continue?')" 
                                wire:loading.attr="disabled"
                                class="px-4 py-2 rounded-xl bg-yellow-500/20 text-yellow-500 border border-yellow-500/30 font-black uppercase tracking-wider text-xs hover:bg-yellow-500 hover:text-white transition-all flex items-center gap-2">
                            <i class="fas fa-stop"></i> Stop
                        </button>
                    @endif
                    @if($serverActions['can_restart'] ?? false)
                        <button wire:click="confirmServerAction('restart', 'Restart Server', 'This will restart your server. Continue?')" 
                                wire:loading.attr="disabled"
                                class="px-4 py-2 rounded-xl bg-electric-blue/20 text-electric-blue border border-electric-blue/30 font-black uppercase tracking-wider text-xs hover:bg-electric-blue hover:text-dark transition-all flex items-center gap-2">
                            <i class="fas fa-sync-alt"></i> Restart
                        </button>
                    @endif
                    <button wire:click="confirmServerAction('poweroff', 'Power Off Server', 'This is a hard shutdown and may cause data loss. Continue?')" 
                            wire:loading.attr="disabled"
                            class="px-4 py-2 rounded-xl bg-red-500/20 text-red-500 border border-red-500/30 font-black uppercase tracking-wider text-xs hover:bg-red-500 hover:text-white transition-all flex items-center gap-2">
                        <i class="fas fa-power-off"></i> Power Off
                    </button>
                    @if($serverActions['can_rebuild'] ?? false)
                        <button wire:click="confirmServerAction('rebuild', 'Rebuild Server', 'This will completely rebuild your server with a fresh OS installation. ALL DATA WILL BE LOST. Continue?')" 
                                wire:loading.attr="disabled"
                                class="px-4 py-2 rounded-xl bg-orange-500/20 text-orange-400 border border-orange-500/30 font-black uppercase tracking-wider text-xs hover:bg-orange-500 hover:text-white transition-all flex items-center gap-2">
                            <i class="fas fa-redo-alt"></i> Rebuild
                        </button>
                    @endif
                </div>
            </div>

            {{-- VM Status Badge with 30s auto-refresh (Task 2.4) --}}
            <div class="mt-4 pt-4 border-t border-white/10 flex flex-wrap items-center justify-between gap-4">
                <div wire:poll.30000ms="refreshVmStatus" class="inline-flex items-center">
                    @if($vmStatus === 'running')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <span class="w-2 h-2 mr-1.5 rounded-full bg-green-400 animate-pulse"></span>
                            Running
                        </span>
                    @elseif($vmStatus === 'stopped')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            <span class="w-2 h-2 mr-1.5 rounded-full bg-red-400"></span>
                            Stopped
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                            <span class="w-2 h-2 mr-1.5 rounded-full bg-gray-400"></span>
                            Unknown
                        </span>
                    @endif
                </div>

                {{-- Quick Actions Panel (Task 2.8) --}}
                <div class="flex flex-wrap gap-2">
                    {{-- Start --}}
                    <button
                        wire:click="startServer"
                        wire:loading.attr="disabled"
                        @disabled($vmStatus === 'running' || $isActionInProgress)
                        class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
                    >
                        <svg wire:loading wire:target="startServer" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Start
                    </button>

                    {{-- Shutdown --}}
                    <button
                        wire:click="confirmShutdown"
                        @disabled($vmStatus === 'stopped' || $isActionInProgress)
                        class="inline-flex items-center px-4 py-2 bg-yellow-500 text-white text-sm font-medium rounded-lg hover:bg-yellow-600 disabled:opacity-50 disabled:cursor-not-allowed transition"
                    >
                        Shutdown
                    </button>

                    {{-- Force Stop --}}
                    <button
                        wire:click="confirmStop"
                        @disabled($vmStatus === 'stopped' || $isActionInProgress)
                        class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
                    >
                        Force Stop
                    </button>

                    {{-- Reboot --}}
                    <button
                        wire:click="confirmReboot"
                        @disabled($vmStatus === 'stopped' || $isActionInProgress)
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
                    >
                        Reboot
                    </button>

                    {{-- Open Console Button (Task 3.2) --}}
                    <button
                        wire:click="openConsole"
                        @disabled($vmStatus === 'stopped' || $isActionInProgress)
                        title="{{ $vmStatus === 'stopped' ? 'Start the VPS to open the console' : 'Open Console' }}"
                        class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
                    >
                        <i class="fas fa-terminal mr-2"></i>
                        Console
                    </button>

                    {{-- Reinstall OS Button (Task 4.9) --}}
                    <button
                        wire:click="showReinstallOptions"
                        @disabled($isActionInProgress)
                        title="Reinstall the operating system"
                        class="inline-flex items-center px-4 py-2 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
                    >
                        <i class="fas fa-redo-alt mr-2"></i>
                        Reinstall OS
                    </button>

                    {{-- Reset Password Button (Task 4.9) --}}
                    <button
                        wire:click="confirmPasswordReset"
                        @disabled($isActionInProgress)
                        title="Reset the root password"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
                    >
                        <i class="fas fa-key mr-2"></i>
                        Reset Password
                    </button>
                </div>
            </div>
        </div>

        {{-- Action Message (Phase 2) --}}
        @if($actionMessage)
            <div class="mb-4 p-4 rounded-xl {{ $actionMessageType === 'success' ? 'bg-green-500/20 border border-green-500/30 text-green-400' : ($actionMessageType === 'error' ? 'bg-red-500/20 border border-red-500/30 text-red-400' : 'bg-blue-500/20 border border-blue-500/30 text-blue-400') }} flex items-center justify-between">
                <span class="text-sm font-medium">
                    <i class="fas {{ $actionMessageType === 'success' ? 'fa-check-circle' : ($actionMessageType === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle') }} mr-2"></i>{{ $actionMessage }}
                </span>
                <button wire:click="$set('actionMessage', '')" class="ml-4 text-gray-400 hover:text-white flex-shrink-0" aria-label="Close message">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        @endif
        @if($showConfirmModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white rounded-xl shadow-xl p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $confirmModalTitle }}</h3>
                <p class="text-gray-600 mb-6">{{ $confirmModalMessage }}</p>
                <div class="flex justify-end gap-3">
                    <button wire:click="cancelConfirmAction" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                        Cancel
                    </button>
                    <button wire:click="executeConfirmedAction" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition">
                        Confirm
                    </button>
                </div>
            </div>
        </div>
        @endif

        {{-- Reinstall OS — Step 1: OS Selector Modal (Task 4.9) --}}
        @if($showReinstallModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60">
            <div class="bg-gray-900 border border-white/10 rounded-2xl shadow-2xl p-6 max-w-md w-full mx-4">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-orange-500/20 flex items-center justify-center">
                        <i class="fas fa-redo-alt text-orange-400"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">Reinstall Operating System</h3>
                        <p class="text-xs text-gray-400">Select the OS to install on your VPS</p>
                    </div>
                </div>

                <div class="space-y-3 mb-6">
                    <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition {{ $selectedOsTemplate === 'ubuntu-22.04' ? 'border-electric-blue bg-electric-blue/10' : 'border-white/10 bg-white/5 hover:border-white/30' }}">
                        <input type="radio" wire:model="selectedOsTemplate" value="ubuntu-22.04" class="sr-only">
                        <div class="w-8 h-8 rounded-lg bg-orange-500/20 flex items-center justify-center flex-shrink-0">
                            <i class="fab fa-ubuntu text-orange-400"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-white">Ubuntu 22.04 LTS</p>
                            <p class="text-xs text-gray-400">Recommended — Long Term Support</p>
                        </div>
                        @if($selectedOsTemplate === 'ubuntu-22.04')
                            <i class="fas fa-check-circle text-electric-blue ml-auto"></i>
                        @endif
                    </label>

                    <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition {{ $selectedOsTemplate === 'debian-12' ? 'border-electric-blue bg-electric-blue/10' : 'border-white/10 bg-white/5 hover:border-white/30' }}">
                        <input type="radio" wire:model="selectedOsTemplate" value="debian-12" class="sr-only">
                        <div class="w-8 h-8 rounded-lg bg-red-500/20 flex items-center justify-center flex-shrink-0">
                            <i class="fab fa-linux text-red-400"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-white">Debian 12 (Bookworm)</p>
                            <p class="text-xs text-gray-400">Stable and lightweight</p>
                        </div>
                        @if($selectedOsTemplate === 'debian-12')
                            <i class="fas fa-check-circle text-electric-blue ml-auto"></i>
                        @endif
                    </label>

                    <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition {{ $selectedOsTemplate === 'windows-server-2022' ? 'border-electric-blue bg-electric-blue/10' : 'border-white/10 bg-white/5 hover:border-white/30' }}">
                        <input type="radio" wire:model="selectedOsTemplate" value="windows-server-2022" class="sr-only">
                        <div class="w-8 h-8 rounded-lg bg-blue-500/20 flex items-center justify-center flex-shrink-0">
                            <i class="fab fa-windows text-blue-400"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-white">Windows Server 2022</p>
                            <p class="text-xs text-gray-400">Datacenter Edition</p>
                        </div>
                        @if($selectedOsTemplate === 'windows-server-2022')
                            <i class="fas fa-check-circle text-electric-blue ml-auto"></i>
                        @endif
                    </label>
                </div>

                <div class="flex justify-end gap-3">
                    <button wire:click="$set('showReinstallModal', false)" class="px-4 py-2 text-sm font-medium text-gray-400 bg-white/5 rounded-lg hover:bg-white/10 transition">
                        Cancel
                    </button>
                    <button wire:click="confirmReinstall" class="px-4 py-2 text-sm font-medium text-white bg-orange-600 rounded-lg hover:bg-orange-700 transition">
                        Next <i class="fas fa-arrow-right ml-1"></i>
                    </button>
                </div>
            </div>
        </div>
        @endif

        {{-- Reinstall OS — Step 2: Data-Loss Warning Modal (Task 4.9) --}}
        @if($showReinstallConfirmModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60">
            <div class="bg-gray-900 border border-red-500/30 rounded-2xl shadow-2xl p-6 max-w-md w-full mx-4">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-red-500/20 flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-red-400"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">⚠️ Confirm OS Reinstall</h3>
                        <p class="text-xs text-red-400">This action is irreversible</p>
                    </div>
                </div>

                <div class="p-4 bg-red-500/10 border border-red-500/30 rounded-xl mb-6">
                    <p class="text-sm text-red-300 font-medium mb-2">
                        <i class="fas fa-trash-alt mr-2"></i>All existing data will be permanently deleted
                    </p>
                    <ul class="text-xs text-red-400 space-y-1 ml-4 list-disc">
                        <li>All files, databases, and configurations will be erased</li>
                        <li>The VM will be rebuilt with <strong class="text-white">{{ match($selectedOsTemplate) { 'ubuntu-22.04' => 'Ubuntu 22.04 LTS', 'debian-12' => 'Debian 12', 'windows-server-2022' => 'Windows Server 2022', default => $selectedOsTemplate } }}</strong></li>
                        <li>New root credentials will be sent to your email</li>
                        <li>This process cannot be stopped once started</li>
                    </ul>
                </div>

                <div class="flex justify-end gap-3">
                    <button wire:click="$set('showReinstallConfirmModal', false)" class="px-4 py-2 text-sm font-medium text-gray-400 bg-white/5 rounded-lg hover:bg-white/10 transition">
                        Cancel
                    </button>
                    <button wire:click="executeReinstall" wire:loading.attr="disabled" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition flex items-center gap-2">
                        <svg wire:loading wire:target="executeReinstall" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Yes, Reinstall Now
                    </button>
                </div>
            </div>
        </div>
        @endif

        {{-- Password Reset Confirmation Modal (Task 4.9) --}}
        @if($showPasswordResetModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60">
            <div class="bg-gray-900 border border-indigo-500/30 rounded-2xl shadow-2xl p-6 max-w-md w-full mx-4">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/20 flex items-center justify-center">
                        <i class="fas fa-key text-indigo-400"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">Reset Root Password</h3>
                        <p class="text-xs text-gray-400">A new password will be generated and emailed to you</p>
                    </div>
                </div>

                <div class="p-4 bg-indigo-500/10 border border-indigo-500/30 rounded-xl mb-6">
                    <p class="text-sm text-indigo-300 mb-2">
                        <i class="fas fa-info-circle mr-2"></i>What will happen:
                    </p>
                    <ul class="text-xs text-gray-400 space-y-1 ml-4 list-disc">
                        <li>Your current root password will be invalidated immediately</li>
                        <li>A new cryptographically random password will be generated</li>
                        <li>The new password will be sent to <strong class="text-white">{{ Auth::user()->email }}</strong></li>
                        <li>The password will <strong class="text-red-400">never</strong> be displayed in the dashboard</li>
                    </ul>
                </div>

                <div class="flex justify-end gap-3">
                    <button wire:click="$set('showPasswordResetModal', false)" class="px-4 py-2 text-sm font-medium text-gray-400 bg-white/5 rounded-lg hover:bg-white/10 transition">
                        Cancel
                    </button>
                    <button wire:click="executePasswordReset" wire:loading.attr="disabled" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition flex items-center gap-2">
                        <svg wire:loading wire:target="executePasswordReset" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Reset Password
                    </button>
                </div>
            </div>
        </div>
        @endif

        {{-- noVNC Console (Task 3.2) --}}
        @if($showConsole && $consoleUrl)
        <div class="mt-4 p-4 bg-white/5 rounded-2xl border border-white/10">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    <i class="fas fa-terminal text-purple-400"></i>
                    Server Console
                </h3>
                <button wire:click="closeConsole" class="text-gray-400 hover:text-white transition text-sm">
                    <i class="fas fa-times mr-1"></i> Close
                </button>
            </div>
            <iframe
                src="{{ $consoleUrl }}"
                style="min-height:600px; width:100%; border:none; border-radius:8px;"
                allowfullscreen
                title="Server Console"
            ></iframe>
        </div>
        @endif


        {{-- OVH-Style Working Tabs --}}
        <div class="flex space-x-1 mb-6 p-1 bg-white/5 rounded-2xl w-fit overflow-x-auto">
            <button wire:click="setHostingTab('home')" class="px-6 py-2 rounded-xl font-black uppercase tracking-wider text-xs {{ $activeHostingTab === 'home' ? 'bg-electric-blue text-dark' : 'text-gray-400 hover:text-white' }} transition-all whitespace-nowrap">
                <i class="fas fa-home mr-1"></i> Home
            </button>
            <button wire:click="setHostingTab('dns_manager')" class="px-6 py-2 rounded-xl font-black uppercase tracking-wider text-xs {{ $activeHostingTab === 'dns_manager' ? 'bg-electric-blue text-dark' : 'text-gray-400 hover:text-white' }} transition-all whitespace-nowrap">
                <i class="fas fa-globe mr-1"></i> DNS Manager
            </button>
            <button wire:click="setHostingTab('dns')" class="px-6 py-2 rounded-xl font-black uppercase tracking-wider text-xs {{ $activeHostingTab === 'dns' ? 'bg-electric-blue text-dark' : 'text-gray-400 hover:text-white' }} transition-all whitespace-nowrap">
                <i class="fas fa-network-wired mr-1"></i> Secondary DNS
            </button>
            <button wire:click="setHostingTab('backup')" class="px-6 py-2 rounded-xl font-black uppercase tracking-wider text-xs {{ $activeHostingTab === 'backup' ? 'bg-electric-blue text-dark' : 'text-gray-400 hover:text-white' }} transition-all whitespace-nowrap">
                <i class="fas fa-shield-alt mr-1"></i> Automated Backup
            </button>
            <button wire:click="setHostingTab('disks')" class="px-6 py-2 rounded-xl font-black uppercase tracking-wider text-xs {{ $activeHostingTab === 'disks' ? 'bg-electric-blue text-dark' : 'text-gray-400 hover:text-white' }} transition-all whitespace-nowrap">
                <i class="fas fa-hdd mr-1"></i> Additional Disk
            </button>
            <button wire:click="setHostingTab('databases')" class="px-6 py-2 rounded-xl font-black uppercase tracking-wider text-xs {{ $activeHostingTab === 'databases' ? 'bg-electric-blue text-dark' : 'text-gray-400 hover:text-white' }} transition-all whitespace-nowrap">
                <i class="fas fa-database mr-1"></i> Databases
            </button>
            <button wire:click="setHostingTab('monitoring')" class="px-6 py-2 rounded-xl font-black uppercase tracking-wider text-xs {{ $activeHostingTab === 'monitoring' ? 'bg-electric-blue text-dark' : 'text-gray-400 hover:text-white' }} transition-all whitespace-nowrap">
                <i class="fas fa-chart-line mr-1"></i> Monitoring
            </button>
            <button wire:click="setHostingTab('migration')" class="px-6 py-2 rounded-xl font-black uppercase tracking-wider text-xs {{ $activeHostingTab === 'migration' ? 'bg-electric-blue text-dark' : 'text-gray-400 hover:text-white' }} transition-all whitespace-nowrap">
                <i class="fas fa-exchange-alt mr-1"></i> Migration
            </button>
            <button wire:click="setHostingTab('import')" class="px-6 py-2 rounded-xl font-black uppercase tracking-wider text-xs {{ $activeHostingTab === 'import' ? 'bg-orange-500 text-dark' : 'text-gray-400 hover:text-white' }} transition-all whitespace-nowrap">
                <i class="fas fa-cloud-download-alt mr-1"></i> Import Server
            </button>
            <button wire:click="setHostingTab('terminal')" class="px-6 py-2 rounded-xl font-black uppercase tracking-wider text-xs {{ $activeHostingTab === 'terminal' ? 'bg-green-500 text-dark' : 'text-gray-400 hover:text-white' }} transition-all whitespace-nowrap">
                <i class="fas fa-terminal mr-1"></i> Console
            </button>
        </div>

        {{-- Tab Content --}}
        @if($activeHostingTab === 'home')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left Column: Your VPS (Server Specs) --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- IP Info Card (Task 3.3) --}}
                <div class="mb-6 p-4 bg-white/5 rounded-2xl border border-white/10">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-electric-blue/20 to-electric-violet/20 flex items-center justify-center">
                            <i class="fas fa-network-wired text-electric-blue"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-white">Network Information</p>
                            <p class="text-xs text-gray-400">Your server's IP and gateway</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- IPv4 Address --}}
                        <div x-data="{ copied: false }" class="p-3 bg-white/5 rounded-xl">
                            <p class="text-xs text-gray-400 mb-1">IPv4 Address</p>
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-sm font-mono text-white">
                                    {{ $selectedHosting->server_ip ?: 'Not assigned yet' }}
                                </span>
                                @if($selectedHosting->server_ip)
                                <button
                                    @click="navigator.clipboard.writeText('{{ $selectedHosting->server_ip }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                    class="text-gray-400 hover:text-electric-blue transition flex-shrink-0"
                                    title="Copy IP"
                                >
                                    <i class="fas" :class="copied ? 'fa-check text-green-400' : 'fa-copy'"></i>
                                </button>
                                @endif
                            </div>
                        </div>
                        {{-- Gateway --}}
                        <div x-data="{ copied: false }" class="p-3 bg-white/5 rounded-xl">
                            <p class="text-xs text-gray-400 mb-1">Gateway</p>
                            @php $gateway = $selectedHosting->gateway ?? $selectedHosting->gateway_ip ?? null; @endphp
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-sm font-mono text-white">
                                    {{ $gateway ?: 'Not assigned yet' }}
                                </span>
                                @if($gateway)
                                <button
                                    @click="navigator.clipboard.writeText('{{ $gateway }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                    class="text-gray-400 hover:text-electric-blue transition flex-shrink-0"
                                    title="Copy Gateway"
                                >
                                    <i class="fas" :class="copied ? 'fa-check text-green-400' : 'fa-copy'"></i>
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Firewall Toggle (Task 3.5) --}}
                <div class="mb-6 p-4 bg-white/5 rounded-2xl border border-white/10">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-electric-blue/20 to-electric-violet/20 flex items-center justify-center">
                                <i class="fas fa-shield-alt {{ $firewallEnabled ? 'text-green-400' : 'text-gray-400' }}"></i>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-white">Firewall</p>
                                <p class="text-xs {{ $firewallEnabled ? 'text-green-400' : 'text-gray-400' }}">
                                    {{ $firewallEnabled ? 'Enabled — your VM is protected' : 'Disabled — your VM is exposed' }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            @if($firewallLoading)
                                <svg class="animate-spin h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            @else
                                <button
                                    wire:click="toggleFirewall({{ $firewallEnabled ? 'false' : 'true' }})"
                                    @disabled($firewallLoading)
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none {{ $firewallEnabled ? 'bg-green-500' : 'bg-gray-600' }}"
                                    role="switch"
                                    aria-checked="{{ $firewallEnabled ? 'true' : 'false' }}"
                                >
                                    <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform {{ $firewallEnabled ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                </button>
                                <span class="text-xs font-medium {{ $firewallEnabled ? 'text-green-400' : 'text-gray-400' }}">
                                    {{ $firewallEnabled ? 'ON' : 'OFF' }}
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Quick SSH fix when firewall is enabled --}}
                    @if($firewallEnabled)
                    <div class="mt-3 pt-3 border-t border-white/10 flex items-center justify-between">
                        <p class="text-xs text-gray-400">
                            <i class="fas fa-terminal mr-1"></i> Can't SSH into your VPS?
                        </p>
                        <button
                            wire:click="allowSshFirewall"
                            wire:loading.attr="disabled"
                            class="px-3 py-1.5 rounded-lg bg-electric-blue/20 border border-electric-blue/30 text-electric-blue text-[10px] font-bold uppercase tracking-wider hover:bg-electric-blue/30 transition cursor-pointer"
                        >
                            <span wire:loading.remove wire:target="allowSshFirewall"><i class="fas fa-unlock text-[10px]"></i> Allow SSH</span>
                            <span wire:loading wire:target="allowSshFirewall"><i class="fas fa-spinner fa-spin text-[10px]"></i></span>
                        </button>
                    </div>
                    @endif
                </div>

                {{-- Server Status Card --}}
                <div class="glass rounded-3xl border border-white/10 overflow-hidden">
                    <div class="p-6 border-b border-white/10">
                        <h3 class="text-sm font-black text-white uppercase tracking-widest">Your VPS</h3>
                    </div>
                    <div class="p-6 space-y-6">
                        {{-- Real-time Server Status --}}
                        <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl">
                            <div class="flex items-center space-x-3">
                                <span class="text-xs text-gray-400">Status</span>
                            </div>
                            @php
                                $serverState = $serverDetails['state'] ?? 'unknown';
                                $statusConfig = match($serverState) {
                                    'running', 'online' => ['class' => 'bg-green-500/20 text-green-500', 'icon' => 'fa-play-circle', 'label' => 'Running'],
                                    'stopped', 'offline' => ['class' => 'bg-yellow-500/20 text-yellow-500', 'icon' => 'fa-stop-circle', 'label' => 'Stopped'],
                                    default => ['class' => 'bg-gray-500/20 text-gray-500', 'icon' => 'fa-question-circle', 'label' => ucfirst($serverState)],
                                };
                            @endphp
                            <span class="px-3 py-1 rounded-full text-xs font-black uppercase {{ $statusConfig['class'] }}">
                                <i class="fas {{ $statusConfig['icon'] }} mr-1"></i>{{ $statusConfig['label'] }}
                            </span>
                        </div>
                        
                        {{-- Server State Indicator with Progress --}}
                        @if(isset($serverDetails['state']))
                        <div class="p-4 bg-white/5 rounded-2xl">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs text-gray-400">Server State</span>
                                <span class="text-xs font-bold {{ $serverState === 'running' ? 'text-green-500' : ($serverState === 'stopped' ? 'text-yellow-500' : 'text-gray-500') }}">{{ ucfirst($serverState) }}</span>
                            </div>
                            <div class="h-2 w-full bg-white/10 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-1000 {{ $serverState === 'running' ? 'bg-green-500 w-full' : ($serverState === 'stopped' ? 'bg-yellow-500 w-1/4' : 'bg-gray-500 w-1/2') }}"></div>
                            </div>
                            <p class="text-[10px] text-gray-500 mt-2">
                                @if($serverState === 'running')
                                    <i class="fas fa-check mr-1 text-green-500"></i>Server is online and operational
                                @elseif($serverState === 'stopped')
                                    <i class="fas fa-pause mr-1 text-yellow-500"></i>Server is powered off
                                @else
                                    <i class="fas fa-info-circle mr-1"></i>Server status unknown or pending
                                @endif
                            </p>
                        </div>
                        @endif

                        @if($selectedHosting->server_hostname || $selectedHosting->primary_domain)
                            <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl group">
                                <span class="text-xs text-gray-400">Name</span>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-bold text-white">{{ $selectedHosting->server_hostname ?? $selectedHosting->primary_domain }}</span>
                                    <button onclick="navigator.clipboard.writeText('{{ $selectedHosting->server_hostname ?? $selectedHosting->primary_domain }}')" class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center text-gray-400 hover:text-electric-blue hover:bg-electric-blue/10 transition-all opacity-0 group-hover:opacity-100">
                                        <i class="fas fa-copy text-xs"></i>
                                    </button>
                                </div>
                            </div>
                        @endif

                        {{-- Change Hostname --}}
                        <div class="p-4 bg-white/5 rounded-2xl">
                            <span class="text-xs text-gray-400 block mb-2">Change Hostname</span>
                            <div class="flex gap-2">
                                <input type="text" 
                                       wire:model="newHostname" 
                                       placeholder="new-hostname.example.com"
                                       class="flex-1 px-3 py-2 rounded-xl bg-white/10 border border-white/20 text-sm text-white placeholder-gray-500 focus:border-electric-blue focus:outline-none">
                                <button wire:click="changeHostname" 
                                        wire:loading.attr="disabled"
                                        class="px-4 py-2 rounded-xl bg-electric-blue/20 text-electric-blue border border-electric-blue/30 text-xs font-bold uppercase hover:bg-electric-blue hover:text-dark transition-all">
                                    <i class="fas fa-save mr-1"></i> Save
                                </button>
                            </div>
                            @error('newHostname') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        @if($selectedHosting->boot_mode)
                            <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl">
                                <span class="text-xs text-gray-400">Boot</span>
                                <span class="text-sm font-bold text-white">{{ $selectedHosting->boot_mode }}</span>
                            </div>
                        @endif

                        @if($selectedHosting->os_name)
                            <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl">
                                <span class="text-xs text-gray-400">OS/Distribution</span>
                                <div class="flex items-center space-x-2">
                                    <i class="fab fa-ubuntu text-electric-blue"></i>
                                    <span class="text-sm font-bold text-white">{{ $selectedHosting->os_name }}</span>
                                </div>
                            </div>
                        @endif

                        @if($selectedHosting->datacenter_location)
                            <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl">
                                <span class="text-xs text-gray-400">Zone</span>
                                <span class="text-sm font-bold text-white">Region {{ $selectedHosting->datacenter_location }}</span>
                            </div>
                        @endif

                        @if($selectedHosting->datacenter_location)
                            <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl">
                                <span class="text-xs text-gray-400">Location</span>
                                <div class="flex items-center space-x-2">
                                    <span class="text-lg">🇸🇬</span>
                                    <span class="text-sm font-bold text-white">{{ $selectedHosting->datacenter_location }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Configuration Card --}}
                <div class="glass rounded-3xl border border-white/10 overflow-hidden">
                    <div class="p-6 border-b border-white/10">
                        <h3 class="text-sm font-black text-white uppercase tracking-widest">Your Configuration</h3>
                    </div>
                    <div class="p-6 space-y-6">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-400">Model</span>
                            <span class="text-sm font-bold text-white">{{ $selectedHosting->plan_name }}</span>
                        </div>

                        @if($selectedHosting->cpu_cores)
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-400">vCores</span>
                                    <span class="text-sm font-bold text-electric-blue">{{ $selectedHosting->cpu_cores }}</span>
                                </div>
                                <p class="text-[10px] text-gray-500">Add vCores by upgrading to the higher range</p>
                            </div>
                        @endif

                        @if($selectedHosting->ram_size)
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-400">Memory</span>
                                    <span class="text-sm font-bold text-electric-blue">{{ $selectedHosting->ram_size }}</span>
                                </div>
                                <div class="h-2 w-full bg-white/10 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-electric-blue to-electric-violet rounded-full" style="width: 45%"></div>
                                </div>
                                <p class="text-[10px] text-gray-500">Add more memory by upgrading to the higher range</p>
                            </div>
                        @endif

                        @if($selectedHosting->storage_size)
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-400">Storage</span>
                                    <span class="text-sm font-bold text-electric-blue">{{ $selectedHosting->storage_size }}</span>
                                </div>
                                @if($selectedHosting->storage_used)
                                    <div class="space-y-1">
                                        <div class="flex items-center justify-between text-[10px]">
                                            <span class="text-gray-500">{{ $selectedHosting->storage_used }} used</span>
                                            <span class="text-gray-500">{{ $selectedHosting->getStorageRemaining() }} free</span>
                                        </div>
                                        <div class="h-2 w-full bg-white/10 rounded-full overflow-hidden">
                                            <div class="h-full bg-gradient-to-r from-electric-blue to-electric-violet rounded-full transition-all duration-1000" style="width: {{ $selectedHosting->getStoragePercentage() }}%"></div>
                                        </div>
                                    </div>
                                @endif
                                <p class="text-[10px] text-gray-500">Add more storage by upgrading to the higher range</p>
                            </div>
                        @endif

                        <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl">
                            <span class="text-xs text-gray-400">Additional disks</span>
                            <span class="text-sm font-bold text-gray-500">Disabled</span>
                        </div>

                        {{-- OS Reinstall Section --}}
                        <div class="mt-6 pt-6 border-t border-white/10">
                            <div class="flex items-center gap-3 mb-4">
                                <i class="fab fa-linux text-electric-blue text-lg"></i>
                                <div>
                                    <p class="text-xs text-gray-400">Operating System</p>
                                    <p class="text-sm font-bold text-white">{{ $selectedHosting->os_name ?? 'Ubuntu 22.04 LTS' }}</p>
                                </div>
                            </div>
                            <button wire:click="confirmServerAction('reinstall', 'Reinstall OS', 'WARNING: This will erase all data on the server and reinstall the OS. This action cannot be undone. Continue?')" 
                                    wire:loading.attr="disabled"
                                    class="w-full py-3 rounded-xl bg-yellow-500/20 text-yellow-500 border border-yellow-500/30 font-black uppercase tracking-wider text-xs hover:bg-yellow-500 hover:text-white transition-all flex items-center justify-center gap-2">
                                <i class="fas fa-redo-alt"></i> Reinstall OS
                            </button>
                            <p class="text-[10px] text-gray-500 mt-2 text-center">
                                <i class="fas fa-exclamation-triangle mr-1 text-yellow-500"></i>
                                All data will be lost during reinstallation
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Backup Section --}}
                @php
                    $retentionDays = $selectedHosting->backup_retention_days ?? 7;
                    $autoBackupCount = \App\Models\VpsSnapshot::where('user_id', Auth::id())
                        ->whereHas('vm', function($q) {
                            $q->where('vmid', $this->selectedHosting->vps_id ?? 0);
                        })
                        ->where('type', 'auto')
                        ->count();
                @endphp
                <div class="glass rounded-3xl border border-white/10 overflow-hidden">
                    <div class="p-6 border-b border-white/10 flex items-center justify-between">
                        <h3 class="text-sm font-black text-white uppercase tracking-widest">Backup</h3>
                        <span class="text-[10px] text-gray-500">Automated backup</span>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl mb-3">
                            <span class="text-xs text-gray-400">Status</span>
                            <span class="text-sm font-bold {{ $selectedHosting->automated_backup ? 'text-green-500' : 'text-gray-500' }}">
                                {{ $selectedHosting->automated_backup ? 'Enabled' : 'Disabled' }}
                            </span>
                        </div>
                        @if($selectedHosting->automated_backup)
                            <div class="flex items-center justify-between p-3 bg-white/5 rounded-2xl mb-3">
                                <span class="text-xs text-gray-400">Retention</span>
                                <span class="text-xs font-bold text-electric-blue">{{ $retentionDays }} Days</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-white/5 rounded-2xl mb-4">
                                <span class="text-xs text-gray-400">Total Backups</span>
                                <span class="text-xs font-bold text-white">{{ $autoBackupCount }}</span>
                            </div>
                        @endif
                        <button wire:click="toggleAutomatedBackup" wire:loading.attr="disabled"
                            class="w-full py-3 rounded-xl {{ $selectedHosting->automated_backup ? 'bg-red-500/20 text-red-500 border border-red-500/30 hover:bg-red-500 hover:text-white' : 'bg-electric-blue/20 text-electric-blue border border-electric-blue/30 hover:bg-electric-blue hover:text-dark' }} font-black uppercase tracking-wider text-xs transition-all flex items-center justify-center gap-2">
                            <i class="fas {{ $selectedHosting->automated_backup ? 'fa-pause' : 'fa-play' }}"></i>
                            {{ $selectedHosting->automated_backup ? 'Disable Backups' : 'Enable Backups' }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- Right Column: IP & Network --}}
            <div class="space-y-6">
                {{-- IP Card with Real-time Data --}}
                <div class="glass rounded-3xl border border-white/10 overflow-hidden">
                    <div class="p-6 border-b border-white/10">
                        <h3 class="text-sm font-black text-white uppercase tracking-widest">IP</h3>
                    </div>
                    <div class="p-6 space-y-6">
                        {{-- IPv4 from Virtualizor API or database --}}
                        @php
                            $ipv4 = $serverDetails['ip'] ?? $serverDetails['network']['ip'] ?? $selectedHosting->server_ip ?? null;
                            $ipv6 = $serverDetails['ipv6'] ?? $serverDetails['network']['ipv6'] ?? $selectedHosting->ipv6 ?? null;
                            $gateway = $serverDetails['gateway'] ?? $selectedHosting->gateway ?? null;
                        @endphp
                        
                        @if($ipv4)
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-400">IPv4</span>
                                    <span class="text-[10px] text-green-500"><i class="fas fa-check-circle mr-1"></i>Active</span>
                                </div>
                                <div class="flex items-center justify-between p-4 bg-electric-blue/10 rounded-2xl border border-electric-blue/20">
                                    <span class="text-sm font-bold text-electric-blue font-mono">{{ $ipv4 }}</span>
                                    <div class="flex items-center space-x-1">
                                        <button onclick="navigator.clipboard.writeText('{{ $ipv4 }}')" class="w-8 h-8 rounded-lg bg-electric-blue/20 flex items-center justify-center text-electric-blue hover:bg-electric-blue hover:text-dark transition-all" title="Copy IP">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($ipv6)
                            <div class="space-y-2">
                                <span class="text-xs text-gray-400">IPv6</span>
                                <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl">
                                    <span class="text-xs font-mono text-gray-300 truncate max-w-[150px]">{{ $ipv6 }}</span>
                                    <button onclick="navigator.clipboard.writeText('{{ $ipv6 }}')" class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center text-gray-400 hover:text-electric-blue hover:bg-electric-blue/10 transition-all">
                                        <i class="fas fa-copy text-xs"></i>
                                    </button>
                                </div>
                            </div>
                        @endif

                        @if($gateway)
                            <div class="space-y-2">
                                <span class="text-xs text-gray-400">Gateway</span>
                                <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl">
                                    <span class="text-sm font-bold text-white font-mono">{{ $gateway }}</span>
                                    <button onclick="navigator.clipboard.writeText('{{ $gateway }}')" class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center text-gray-400 hover:text-electric-blue hover:bg-electric-blue/10 transition-all">
                                        <i class="fas fa-copy text-xs"></i>
                                    </button>
                                </div>
                            </div>
                        @endif

                        {{-- Network Stats --}}
                        @if(isset($serverDetails['bandwidth']))
                        <div class="p-4 bg-white/5 rounded-2xl">
                            <span class="text-xs text-gray-400 block mb-2">Bandwidth Usage</span>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-electric-blue"><i class="fas fa-arrow-up mr-1"></i>{{ $serverDetails['bandwidth']['used'] ?? 'N/A' }}</span>
                                <span class="text-gray-500">/ {{ $serverDetails['bandwidth']['limit'] ?? 'Unlimited' }}</span>
                            </div>
                            <div class="h-1.5 w-full bg-white/10 rounded-full mt-2">
                                <div class="h-full bg-electric-blue rounded-full" style="width: {{ $serverDetails['bandwidth']['percentage'] ?? 0 }}%"></div>
                            </div>
                        </div>
                        @endif

                        <div class="space-y-2">
                            <span class="text-xs text-gray-400">Secondary DNS</span>
                            <p class="text-[10px] text-gray-500">No domains configured</p>
                            <button wire:click="setHostingTab('dns')" class="w-full py-3 rounded-xl bg-electric-blue/10 text-electric-blue font-black uppercase tracking-widest text-[10px] hover:bg-electric-blue hover:text-dark transition-all">
                                Add a domain name
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Terminal Access Card --}}
                @if($serverActions['can_reinstall'] ?? false)
                <div class="glass rounded-3xl border border-white/10 p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-green-500/20 flex items-center justify-center">
                            <i class="fas fa-terminal text-green-500"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-white uppercase tracking-widest">In-Browser Terminal</h3>
                            <p class="text-xs text-gray-400">No SSH client needed</p>
                        </div>
                    </div>
                    <button wire:click="setHostingTab('terminal')"
                            class="w-full py-3 rounded-xl bg-green-500/20 text-green-400 border border-green-500/30 font-black uppercase tracking-wider text-xs hover:bg-green-500 hover:text-white transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-terminal"></i> Open Terminal
                    </button>
                </div>
                @endif

                {{-- Connection Details Card --}}
                @if($selectedHosting->root_password || $selectedHosting->control_panel_username)
                    <div class="glass rounded-3xl border border-white/10 overflow-hidden">
                        <div class="p-6 border-b border-white/10">
                            <h3 class="text-sm font-black text-white uppercase tracking-widest">Connection Details</h3>
                        </div>
                        <div class="p-6 space-y-4">
                            @if($selectedHosting->root_password)
                                <div class="space-y-2">
                                    <span class="text-xs text-gray-400">Root Password</span>
                                    <div class="flex items-center p-4 bg-white/5 rounded-2xl">
                                        <span class="text-sm font-bold text-white font-mono">••••••••••••</span>
                                        <span class="ml-3 text-[10px] text-gray-500">Stored securely. Use "Reset Root Password" to receive a new password by email.</span>
                                    </div>
                                </div>
                            @endif

                            @if($selectedHosting->control_panel_username)
                                <div class="space-y-2">
                                    <span class="text-xs text-gray-400">Control Panel User</span>
                                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl">
                                        <span class="text-sm font-bold text-white">{{ $selectedHosting->control_panel_username }}</span>
                                        <button onclick="navigator.clipboard.writeText('{{ $selectedHosting->control_panel_username }}')" class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center text-gray-400 hover:text-electric-blue hover:bg-electric-blue/10 transition-all">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- My Offer / Plan Details --}}
                <div class="glass rounded-3xl border border-white/10 overflow-hidden">
                    <div class="p-6 border-b border-white/10">
                        <h3 class="text-sm font-black text-white uppercase tracking-widest">My Offer</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-400">Commitment date</span>
                            <span class="text-sm font-bold text-white">{{ $selectedHosting->start_date?->format('d F Y') ?? 'N/A' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-400">Expiry date</span>
                            <span class="text-sm font-bold {{ $selectedHosting->isExpiringSoon() ? 'text-red-500' : 'text-white' }}">
                                {{ $selectedHosting->expiry_date?->format('d F Y') ?? 'N/A' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between p-4 bg-electric-blue/10 rounded-2xl mt-4">
                            <span class="text-xs text-electric-blue font-black uppercase">Monthly Price</span>
                            <span class="text-lg font-black text-electric-blue">{{ $currencySymbol }}{{ number_format($selectedHosting->price, 2) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Streaming Addon Card --}}
                @if(isset($selectedHosting->has_streaming_addon))
                <div class="glass rounded-3xl border {{ $selectedHosting->has_streaming_addon ? 'border-purple-500/30' : 'border-white/10' }} overflow-hidden">
                    <div class="p-6 border-b border-white/10 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg {{ $selectedHosting->has_streaming_addon ? 'bg-purple-500/20' : 'bg-white/5' }} flex items-center justify-center">
                                <i class="fas fa-broadcast-tower {{ $selectedHosting->has_streaming_addon ? 'text-purple-400' : 'text-gray-400' }}"></i>
                            </div>
                            <h3 class="text-sm font-black text-white uppercase tracking-widest">Live Stream</h3>
                        </div>
                        @if($selectedHosting->has_streaming_addon)
                            <span class="px-2 py-1 rounded-lg bg-purple-500/20 text-purple-400 text-[10px] font-bold uppercase tracking-wider">Active</span>
                        @else
                            <span class="px-2 py-1 rounded-lg bg-gray-500/20 text-gray-400 text-[10px] font-bold uppercase tracking-wider">Inactive</span>
                        @endif
                    </div>
                    <div class="p-6">
                        @if($selectedHosting->has_streaming_addon)
                            @php
                                $streamApiKey = \App\Models\StreamingApiKey::where('hosting_id', $selectedHosting->id)
                                    ->orWhereHas('user', function($q) use ($selectedHosting) {
                                        $q->where('id', $selectedHosting->user_id);
                                    })
                                    ->latest()->first();
                            @endphp
                            @if($streamApiKey)
                                <div class="space-y-3">
                                    <div class="p-3 bg-purple-500/10 rounded-xl border border-purple-500/20">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-[10px] text-purple-400 uppercase tracking-wider font-bold">App ID</span>
                                            <button onclick="navigator.clipboard.writeText('{{ $streamApiKey->app_id }}')" class="text-purple-400 hover:text-white transition-colors text-xs" title="Copy">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                        <p class="text-sm font-mono text-white break-all">{{ $streamApiKey->app_id }}</p>
                                    </div>
                                    <div class="p-3 bg-purple-500/10 rounded-xl border border-purple-500/20" x-data="{ certRevealed: false }">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-[10px] text-purple-400 uppercase tracking-wider font-bold">App Certificate</span>
                                            <div class="flex items-center gap-2">
                                                <button @click="certRevealed = !certRevealed" class="text-purple-400 hover:text-white transition-colors text-xs" title="Show/Hide">
                                                    <i class="fas" :class="certRevealed ? 'fa-eye-slash' : 'fa-eye'"></i>
                                                </button>
                                                <button onclick="navigator.clipboard.writeText('{{ $streamApiKey->app_certificate }}')" class="text-purple-400 hover:text-white transition-colors text-xs" title="Copy">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <p class="text-sm font-mono text-white break-all" x-show="!certRevealed">••••••••••••••••••••••••••••••••••••••••••••••••••</p>
                                        <p class="text-sm font-mono text-white break-all" x-show="certRevealed" x-cloak>{{ $streamApiKey->app_certificate }}</p>
                                    </div>
                                    <div class="p-3 bg-purple-500/10 rounded-xl border border-purple-500/20">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-[10px] text-purple-400 uppercase tracking-wider font-bold">RTMP Ingest URL</span>
                                            <button onclick="navigator.clipboard.writeText('{{ $streamApiKey->rtmp_ingest_url }}')" class="text-purple-400 hover:text-white transition-colors text-xs" title="Copy">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                        <p class="text-sm font-mono text-white break-all">{{ $streamApiKey->rtmp_ingest_url }}</p>
                                    </div>
                                    <div class="p-3 bg-purple-500/10 rounded-xl border border-purple-500/20">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-[10px] text-purple-400 uppercase tracking-wider font-bold">Playback URL</span>
                                            <button onclick="navigator.clipboard.writeText('{{ $streamApiKey->playback_url }}')" class="text-purple-400 hover:text-white transition-colors text-xs" title="Copy">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                        <p class="text-sm font-mono text-white break-all">{{ $streamApiKey->playback_url }}</p>
                                    </div>
                                    {{-- Recording Toggle --}}
                                    <div class="p-3 bg-orange-500/10 rounded-xl border border-orange-500/20 mt-2">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <i class="fas fa-video text-orange-400 text-xs"></i>
                                                <span class="text-xs font-bold text-orange-400 uppercase tracking-wider">Stream Recording</span>
                                            </div>
                                            <button
                                                wire:click="toggleRecording"
                                                class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors {{ $selectedHosting->recording_enabled ? 'bg-orange-500' : 'bg-gray-600' }}">
                                                <span class="inline-block h-3 w-3 transform rounded-full bg-white transition {{ $selectedHosting->recording_enabled ? 'translate-x-5' : 'translate-x-1' }}"></span>
                                            </button>
                                        </div>
                                        <p class="text-[10px] text-gray-500 mt-1">
                                            {{ $selectedHosting->recording_enabled ? 'All streams are being recorded. Auto-deleted after 30 days.' : 'Recording is OFF. Toggle to enable.' }}
                                        </p>
                                    </div>

                                    {{-- Recording History --}}
                                    @php
                                        $recordings = \App\Models\StreamRecording::where('hosting_id', $selectedHosting->id)
                                            ->whereIn('status', ['recording', 'completed'])
                                            ->orderBy('started_at', 'desc')
                                            ->limit(5)
                                            ->get();
                                    @endphp
                                    @if($recordings->count() > 0)
                                    <div class="mt-2">
                                        <h4 class="text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2">Recent Recordings</h4>
                                        <div class="space-y-1.5">
                                            @foreach($recordings as $rec)
                                            <div class="flex items-center justify-between p-2 bg-black/20 rounded-lg border border-white/5">
                                                <div class="flex items-center gap-2">
                                                    <i class="fas fa-circle text-[8px] {{ $rec->status === 'recording' ? 'text-red-500 animate-pulse' : 'text-green-400' }}"></i>
                                                    <span class="text-xs text-gray-300">{{ $rec->recording_name }}</span>
                                                </div>
                                                <div class="flex items-center gap-2 text-[10px] text-gray-500">
                                                    <span>{{ $rec->duration_seconds > 0 ? $rec->duration_formatted : '00:00' }}</span>
                                                    <span>{{ $rec->file_size_mb > 0 ? number_format($rec->file_size_mb, 1).' MB' : '' }}</span>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif

                                    <a href="{{ route('client.dashboard') }}?tab=streaming" class="w-full py-2 rounded-xl bg-purple-500 text-white border border-purple-500 font-black uppercase tracking-wider text-xs hover:bg-purple-600 transition-all flex items-center justify-center gap-2 mt-2">
                                        <i class="fas fa-external-link-alt"></i> Full Streaming Dashboard
                                    </a>
                                </div>
                            @else
                                <p class="text-xs text-gray-400 mb-3">Streaming addon active but API keys not found. Contact support.</p>
                                <a href="{{ route('client.dashboard') }}?tab=streaming" class="w-full py-3 rounded-xl bg-purple-500/20 text-purple-400 border border-purple-500/30 font-black uppercase tracking-wider text-xs hover:bg-purple-500 hover:text-white transition-all flex items-center justify-center gap-2">
                                    <i class="fas fa-broadcast-tower"></i> Open Streaming Dashboard
                                </a>
                            @endif
                        @else
                            <p class="text-xs text-gray-400 mb-3">Add live streaming capability to this VPS. One-click deploy with SRS server.</p>
                            @php
                                // Get VPS plan's streaming addon price (set in admin)
                                $vpsPlan = \App\Models\VpsPlan::where('name', $selectedHosting->plan_name ?? $selectedHosting->server_type)->first();
                                $addonPrice = $vpsPlan?->streaming_addon_price ?? 0;

                                // Fallback to generic streaming addon plan price
                                if ($addonPrice <= 0) {
                                    $addonPlan = \App\Models\StreamingPlan::addon()->vpsEmbedded()->active()->orderBy('addon_price')->first();
                                    $addonPrice = $addonPlan?->addon_price ?? 0;
                                }

                                // Calculate prorated price for remaining days
                                $expiryDate = $selectedHosting->expiry_date ? \Carbon\Carbon::parse($selectedHosting->expiry_date) : null;
                                $today = \Carbon\Carbon::today();
                                $proratedPrice = $addonPrice;
                                $daysRemaining = null;
                                if ($expiryDate && $expiryDate->isFuture()) {
                                    $daysRemaining = max(1, $today->diffInDays($expiryDate));
                                    $daysInMonth = max(1, $today->daysInMonth);
                                    $proratedPrice = round($addonPrice * ($daysRemaining / $daysInMonth), 2);
                                }
                            @endphp
                            @if($addonPrice > 0)
                                <div class="mb-3 p-3 bg-white/5 rounded-xl">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-gray-400">Monthly Price</span>
                                        <span class="text-sm font-bold text-white">{{ $currencySymbol }}{{ number_format($addonPrice, 2) }}/mo</span>
                                    </div>
                                    @if($daysRemaining && $daysRemaining < 30)
                                        <div class="flex items-center justify-between mt-1 pt-1 border-t border-white/5">
                                            <span class="text-[10px] text-purple-400">Prorated ({{ $daysRemaining }} days left)</span>
                                            <span class="text-xs font-bold text-purple-400">{{ $currencySymbol }}{{ number_format($proratedPrice, 2) }} due now</span>
                                        </div>
                                    @endif
                                </div>
                                <a href="{{ route('client.streaming.activate', $selectedHosting) }}" class="w-full py-3 rounded-xl bg-purple-500/20 text-purple-400 border border-purple-500/30 font-black uppercase tracking-wider text-xs hover:bg-purple-500 hover:text-white transition-all flex items-center justify-center gap-2">
                                    <i class="fas fa-plus"></i> Activate Streaming Addon
                                </a>
                            @else
                                <p class="text-xs text-yellow-400">No streaming addon plans available. Contact support.</p>
                            @endif
                        @endif
                    </div>
                </div>
                @endif

                {{-- Quick Actions --}}
                <div class="glass rounded-3xl border border-white/10 p-6">
                    <h3 class="text-sm font-black text-white uppercase tracking-widest mb-4">Quick Actions</h3>
                    <div class="space-y-3">
                        @if($selectedHosting->control_panel_url)
                            <a href="{{ $selectedHosting->control_panel_url }}" target="_blank" class="flex items-center justify-center w-full py-3 rounded-xl bg-electric-blue text-dark font-black uppercase tracking-widest text-xs hover:scale-105 transition-all">
                                <i class="fas fa-external-link-alt mr-2"></i>Open Control Panel
                            </a>
                        @endif
                        
                        {{-- Rescue Mode Button --}}
                        <button wire:click="confirmServerAction('rescue', 'Boot Rescue Mode', 'This will reboot your server into rescue mode for system recovery. Continue?')" 
                                wire:loading.attr="disabled"
                                class="flex items-center justify-center w-full py-3 rounded-xl bg-orange-500/20 text-orange-500 border border-orange-500/30 font-black uppercase tracking-widest text-xs hover:bg-orange-500 hover:text-white transition-all">
                            <i class="fas fa-life-ring mr-2"></i>Rescue Mode
                        </button>
                        
                        {{-- Reset Root Password --}}
                        <button wire:click="confirmServerAction('reset_password', 'Reset Root Password', 'A new root password will be generated and sent to your email. Continue?')" 
                                wire:loading.attr="disabled"
                                class="flex items-center justify-center w-full py-3 rounded-xl bg-yellow-500/20 text-yellow-500 border border-yellow-500/30 font-black uppercase tracking-widest text-xs hover:bg-yellow-500 hover:text-white transition-all">
                            <i class="fas fa-key mr-2"></i>Reset Root Password
                        </button>
                        
                        <a href="{{ route('services.index') }}" class="flex items-center justify-center w-full py-3 rounded-xl bg-white/10 text-white font-black uppercase tracking-widest text-xs hover:bg-white/20 transition-all">
                            <i class="fas fa-arrow-up mr-2"></i>Upgrade Plan
                        </a>
                        <button wire:click="switchTab('tickets')" class="flex items-center justify-center w-full py-3 rounded-xl bg-white/5 text-gray-400 font-black uppercase tracking-widest text-xs hover:bg-white/10 hover:text-white transition-all">
                            <i class="fas fa-headset mr-2"></i>Need Support?
                        </button>
                    </div>
                </div>

                {{-- Advanced Actions --}}
                <div class="glass rounded-3xl border border-red-500/20 p-6 bg-red-500/5">
                    <h3 class="text-sm font-black text-red-400 uppercase tracking-widest mb-4"><i class="fas fa-exclamation-triangle mr-2"></i>Advanced</h3>
                    <div class="space-y-3">
                        <button wire:click="confirmServerAction('rebuild', 'Rebuild Server', 'This will completely rebuild your server with a fresh OS installation. ALL DATA WILL BE LOST. Continue?')" 
                                wire:loading.attr="disabled"
                                class="flex items-center justify-center w-full py-3 rounded-xl bg-red-500/20 text-red-500 border border-red-500/30 font-black uppercase tracking-widest text-xs hover:bg-red-500 hover:text-white transition-all">
                            <i class="fas fa-hammer mr-2"></i>Rebuild Server
                        </button>
                        <button wire:click="confirmServerAction('terminate', 'Terminate Server', 'WARNING: This will permanently delete your server and all associated data. This action CANNOT be undone. Continue?')" 
                                wire:loading.attr="disabled"
                                class="flex items-center justify-center w-full py-3 rounded-xl bg-red-500/10 text-red-400 border border-red-500/20 font-black uppercase tracking-widest text-xs hover:bg-red-500 hover:text-white transition-all">
                            <i class="fas fa-trash-alt mr-2"></i>Terminate Server
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Analytics Engine: Module 2 --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
            @include('livewire.dashboard.analytics-cpu')
            @include('livewire.dashboard.analytics-ram')
            @include('livewire.dashboard.analytics-bandwidth')
        </div>
    </div>

    {{-- DNS Manager Tab Content (Task 4.4) --}}
    @elseif($activeHostingTab === 'dns_manager')
    <div class="animate-in fade-in duration-300 space-y-6" wire:key="dns-manager-tab">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-black text-white uppercase tracking-wider flex items-center gap-2">
                    <i class="fas fa-globe text-electric-blue"></i> DNS Manager
                </h3>
                <p class="text-xs text-gray-400 mt-1 flex items-center flex-wrap gap-2">
                    <span>Domain:</span>
                    @if($editingDomain)
                        <span class="flex items-center gap-2">
                            <input type="text" wire:model="editDomainValue"
                                   class="px-2 py-1 rounded-lg bg-white/10 border border-white/20 text-xs text-white font-mono focus:border-electric-blue focus:outline-none w-48"
                                   placeholder="example.com">
                            <button wire:click="saveDomainName" wire:loading.attr="disabled"
                                    class="px-2 py-1 bg-electric-blue text-dark text-[10px] font-bold uppercase rounded-lg hover:bg-electric-blue/80 transition">
                                <span wire:loading.remove wire:target="saveDomainName">Save</span>
                                <span wire:loading wire:target="saveDomainName"><i class="fas fa-spinner fa-spin"></i></span>
                            </button>
                            <button wire:click="cancelEditDomain"
                                    class="px-2 py-1 bg-white/5 text-gray-400 text-[10px] font-bold uppercase rounded-lg hover:bg-white/10 transition">
                                Cancel
                            </button>
                        </span>
                    @else
                        <span class="text-electric-blue font-mono">{{ $selectedHosting->primary_domain ?? 'Not set' }}</span>
                        <button wire:click="startEditDomain"
                                class="text-gray-500 hover:text-electric-blue transition" title="Edit domain">
                            <i class="fas fa-pen text-[10px]"></i>
                        </button>
                    @endif
                    <span>&nbsp;&bull;&nbsp; Nameservers:</span>
                    @php
                        $dnsNs = $selectedHosting?->domain?->nameservers ?? [];
                        $dnsNsList = is_string($dnsNs) ? (json_decode($dnsNs, true) ?? []) : (is_array($dnsNs) ? $dnsNs : []);
                    @endphp
                    @if(!empty($dnsNsList))
                        <span class="text-green-400 font-mono">{{ implode(', ', $dnsNsList) }}</span>
                    @else
                        <span class="text-gray-500 font-mono">Not configured</span>
                    @endif
                    <button type="button" wire:click="openNsModal" wire:loading.attr="disabled"
                            class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-electric-blue/20 border border-electric-blue/30 text-electric-blue text-[10px] font-bold uppercase tracking-wider hover:bg-electric-blue/30 transition cursor-pointer">
                        <span wire:loading.remove wire:target="openNsModal"><i class="fas fa-pen text-[10px]"></i> Edit</span>
                        <span wire:loading wire:target="openNsModal"><i class="fas fa-spinner fa-spin text-[10px]"></i></span>
                    </button>
                </p>
                @if($editDomainError)
                <p class="text-xs text-red-400 mt-1">{{ $editDomainError }}</p>
                @endif
            </div>
            <button wire:click="openDnsAddForm('A')"
                    class="inline-flex items-center px-4 py-2 bg-electric-blue text-dark text-xs font-bold uppercase rounded-xl hover:bg-electric-blue/80 transition">
                <i class="fas fa-plus mr-2"></i> Add Record
            </button>
        </div>

        {{-- Add / Edit Form (pure Livewire — no Alpine) --}}
        @if($showDnsForm)
        <div class="glass rounded-2xl border border-white/10 p-6" wire:key="dns-form">
            <h4 class="text-sm font-bold text-white mb-4">
                {{ $editingDnsRecordId ? 'Edit DNS Record' : 'New DNS Record' }}
            </h4>

            @if($dnsFormError)
            <div class="mb-4 p-3 bg-red-500/20 border border-red-500/30 rounded-xl text-red-400 text-sm">
                <i class="fas fa-exclamation-circle mr-2"></i>{{ $dnsFormError }}
            </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                {{-- Type --}}
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Type</label>
                    <select wire:model.live="dnsFormType"
                            class="w-full px-3 py-2 rounded-xl bg-white/10 border border-white/20 text-sm text-white focus:border-electric-blue focus:outline-none">
                        @foreach(['A','AAAA','CNAME','MX','TXT','NS','SRV','CAA'] as $rt)
                            <option value="{{ $rt }}" {{ $dnsFormType === $rt ? 'selected' : '' }}>{{ $rt }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Hostname --}}
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Hostname</label>
                    <input type="text" wire:model="dnsFormName" placeholder="@ or subdomain"
                           class="w-full px-3 py-2 rounded-xl bg-white/10 border border-white/20 text-sm text-white placeholder-gray-500 focus:border-electric-blue focus:outline-none">
                </div>

                {{-- Value --}}
                <div>
                    <label class="block text-xs text-gray-400 mb-1">
                        @php
                            $valueLabels = ['A'=>'IPv4 Address','AAAA'=>'IPv6 Address','CNAME'=>'Target Hostname','MX'=>'Mail Server','TXT'=>'TXT Value','NS'=>'Nameserver','SRV'=>'Service Target','CAA'=>'CAA Value'];
                        @endphp
                        {{ $valueLabels[$dnsFormType] ?? 'Value' }}
                    </label>
                    <input type="text" wire:model="dnsFormValue"
                           placeholder="{{ ['A'=>$selectedHosting->server_ip ?? '0.0.0.0','AAAA'=>'2001:db8::1','CNAME'=>'target.example.com','MX'=>'mail.example.com','TXT'=>'v=spf1 ...','NS'=>'ns1.believoo.com','SRV'=>'10 20 443 target.example.com','CAA'=>'0 issue "letsencrypt.org"'][$dnsFormType] ?? 'value' }}"
                           class="w-full px-3 py-2 rounded-xl bg-white/10 border border-white/20 text-sm text-white placeholder-gray-500 focus:border-electric-blue focus:outline-none">
                </div>

                {{-- TTL --}}
                <div>
                    <label class="block text-xs text-gray-400 mb-1">TTL</label>
                    <select wire:model="dnsFormTtl"
                            class="w-full px-3 py-2 rounded-xl bg-white/10 border border-white/20 text-sm text-white focus:border-electric-blue focus:outline-none">
                        <option value="300">300s (5 min)</option>
                        <option value="3600">3600s (1 hr)</option>
                        <option value="14400">14400s (4 hr)</option>
                        <option value="86400">86400s (1 day)</option>
                    </select>
                </div>

                {{-- Priority (MX/SRV/CAA only) --}}
                @if(in_array($dnsFormType, ['MX','SRV','CAA']))
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Priority</label>
                    <input type="number" wire:model="dnsFormPriority" placeholder="10" min="0" max="65535"
                           class="w-full px-3 py-2 rounded-xl bg-white/10 border border-white/20 text-sm text-white placeholder-gray-500 focus:border-electric-blue focus:outline-none">
                </div>
                @endif
            </div>

            <div class="flex gap-3">
                <button wire:click="saveDnsRecord" wire:loading.attr="disabled"
                        class="px-4 py-2 bg-electric-blue text-dark text-xs font-bold uppercase rounded-xl hover:bg-electric-blue/80 transition">
                    <span wire:loading.remove wire:target="saveDnsRecord"><i class="fas fa-save mr-1"></i> Save Record</span>
                    <span wire:loading wire:target="saveDnsRecord"><i class="fas fa-spinner fa-spin mr-1"></i> Saving...</span>
                </button>
                <button wire:click="$set('showDnsForm', false)"
                        class="px-4 py-2 bg-white/5 text-gray-400 text-xs font-bold uppercase rounded-xl hover:bg-white/10 transition">
                    Cancel
                </button>
            </div>
        </div>
        @endif

        {{-- All DNS Records Table --}}
        <div class="glass rounded-3xl border border-white/10 overflow-hidden" wire:key="dns-records-table">
            <div class="p-5 border-b border-white/10 flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-black text-white uppercase tracking-widest">DNS Records</h4>
                    <p class="text-xs text-gray-400 mt-1">A, AAAA, CNAME, MX, TXT, NS, SRV, PTR</p>
                </div>
                <button wire:click="loadDnsRecords" class="p-2 rounded-lg bg-white/5 text-gray-400 hover:text-electric-blue transition" title="Refresh">
                    <i class="fas fa-sync-alt text-xs" wire:loading.class="animate-spin" wire:target="loadDnsRecords"></i>
                </button>
            </div>
            <div class="p-5">
                @php
                    $allDnsRecords = $selectedHosting->domain
                        ? \App\Models\DnsRecord::where('user_domain_id', $selectedHosting->domain->id)
                            ->where('is_active', true)
                            ->orderBy('record_type')->orderBy('name')->get()
                        : collect();
                @endphp

                @if($allDnsRecords->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs text-gray-400 uppercase tracking-wider border-b border-white/10">
                                <th class="pb-3 text-left w-20">Type</th>
                                <th class="pb-3 text-left">Hostname</th>
                                <th class="pb-3 text-left">Value</th>
                                <th class="pb-3 text-left w-12">Pri</th>
                                <th class="pb-3 text-left w-20">TTL</th>
                                <th class="pb-3 text-right w-24">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach($allDnsRecords as $rec)
                            @php
                                $tc = match($rec->record_type) {
                                    'A'     => 'bg-blue-500/20 text-blue-400',
                                    'AAAA'  => 'bg-indigo-500/20 text-indigo-400',
                                    'CNAME' => 'bg-green-500/20 text-green-400',
                                    'MX'    => 'bg-orange-500/20 text-orange-400',
                                    'TXT'   => 'bg-yellow-500/20 text-yellow-400',
                                    'NS'    => 'bg-purple-500/20 text-purple-400',
                                    'SRV'   => 'bg-pink-500/20 text-pink-400',
                                    'PTR'   => 'bg-teal-500/20 text-teal-400',
                                    default => 'bg-gray-500/20 text-gray-400',
                                };
                            @endphp
                            <tr class="hover:bg-white/5 transition" wire:key="dns-rec-{{ $rec->id }}">
                                <td class="py-3">
                                    <span class="px-2 py-1 rounded-lg text-xs font-bold {{ $tc }}">{{ $rec->record_type }}</span>
                                </td>
                                <td class="py-3 font-mono text-white text-xs">{{ $rec->name }}</td>
                                <td class="py-3 font-mono text-gray-300 text-xs max-w-xs truncate" title="{{ $rec->value }}">{{ $rec->value }}</td>
                                <td class="py-3 text-gray-400 text-xs">{{ $rec->priority ?? '—' }}</td>
                                <td class="py-3 text-gray-400 text-xs">{{ $rec->ttl }}s</td>
                                <td class="py-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button wire:click="openDnsEditForm({{ $rec->id }})"
                                                class="p-1.5 rounded-lg bg-white/5 text-gray-400 hover:text-electric-blue hover:bg-electric-blue/10 transition" title="Edit">
                                            <i class="fas fa-edit text-xs"></i>
                                        </button>
                                        <button wire:click="deleteDnsRecord({{ $rec->id }})"
                                                wire:confirm="Delete this {{ $rec->record_type }} record?"
                                                class="p-1.5 rounded-lg bg-white/5 text-gray-400 hover:text-red-400 hover:bg-red-500/10 transition" title="Delete">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-10">
                    <i class="fas fa-database text-gray-600 text-4xl mb-3"></i>
                    <p class="text-gray-400 text-sm">No DNS records yet.</p>
                    <p class="text-gray-500 text-xs mt-1">Click "Add Record" above to create your first entry.</p>
                </div>
                @endif
            </div>
        </div>

        {{-- PTR / Reverse DNS --}}
        <div class="glass rounded-3xl border border-white/10 overflow-hidden" wire:key="ptr-section">
            <div class="p-6 border-b border-white/10 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500/20 to-pink-500/20 flex items-center justify-center">
                        <i class="fas fa-exchange-alt text-purple-400"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-white uppercase tracking-widest">Reverse DNS (PTR)</h3>
                        <p class="text-xs text-gray-400">Map your IP to a hostname</p>
                    </div>
                </div>
                <button wire:click="showSetPtrForm"
                        class="inline-flex items-center px-4 py-2 bg-purple-500/20 text-purple-400 border border-purple-500/30 text-xs font-bold uppercase rounded-xl hover:bg-purple-500 hover:text-white transition-all">
                    <i class="fas fa-edit mr-2"></i> Set Reverse DNS
                </button>
            </div>
            <div class="p-6">
                <div class="mb-4 p-4 bg-white/5 rounded-xl flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-400 mb-1">PTR for {{ $selectedHosting->server_ip ?? 'N/A' }}</p>
                        @if($dnsPtrRecord)
                            <p class="text-sm font-mono text-white">{{ $dnsPtrRecord['value'] ?? ($dnsPtrRecord['name'] ?? '') }}</p>
                        @else
                            <p class="text-sm text-gray-500 italic">Not set</p>
                        @endif
                    </div>
                    @if($dnsPtrRecord)
                        <span class="px-2 py-1 rounded-lg text-xs font-bold bg-teal-500/20 text-teal-400">PTR</span>
                    @endif
                </div>
                @if($showPtrForm)
                <div class="p-4 bg-white/5 rounded-xl mb-4" wire:key="ptr-form">
                    <h4 class="text-sm font-bold text-white mb-3">Set Reverse DNS</h4>
                    <div class="mb-3">
                        <label class="block text-xs text-gray-400 mb-1">FQDN (e.g. vps.unilive.me)</label>
                        <input type="text" wire:model="ptrHostname" placeholder="vps.unilive.me"
                               class="w-full px-3 py-2 rounded-xl bg-white/10 border border-white/20 text-sm text-white placeholder-gray-500 focus:border-electric-blue focus:outline-none">
                    </div>
                    <div class="flex gap-3">
                        <button wire:click="savePtrRecord" class="px-4 py-2 bg-purple-600 text-white text-xs font-bold uppercase rounded-xl hover:bg-purple-700 transition">
                            <i class="fas fa-save mr-1"></i> Save PTR
                        </button>
                        <button wire:click="$set('showPtrForm', false)" class="px-4 py-2 bg-white/5 text-gray-400 text-xs font-bold uppercase rounded-xl hover:bg-white/10 transition">
                            Cancel
                        </button>
                    </div>
                </div>
                @endif
                <div class="p-3 bg-yellow-500/10 border border-yellow-500/20 rounded-xl">
                    <p class="text-xs text-yellow-400"><i class="fas fa-clock mr-2"></i><strong>Note:</strong> PTR propagation may take up to 24 hours.</p>
                </div>
            </div>
        </div>

    </div>

    {{-- Terminal Tab Content --}}
    @elseif($activeHostingTab === 'terminal')
    @php
        $vpsId    = $selectedHosting->vps_id ?? null;
        $serverIp = $selectedHosting->server_ip ?? null;
        $isRunning = ($serverDetails['state'] ?? '') === 'running';
    @endphp
    <div class="animate-in fade-in duration-300">
        <div class="glass rounded-3xl border border-white/10 overflow-hidden">
            <div class="p-6 border-b border-white/10 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-green-500/20 flex items-center justify-center">
                        <i class="fas fa-terminal text-green-500 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-white uppercase tracking-wider">VM Console</h3>
                        <p class="text-sm text-gray-400">Browser-based terminal via Proxmox noVNC</p>
                    </div>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-black uppercase bg-green-500/20 text-green-500 border border-green-500/30">
                    <i class="fas fa-lock mr-1"></i>Secure
                </span>
            </div>
            <div class="p-6 space-y-4">

                {{-- SSH Quick Access --}}
                <div class="p-4 bg-black/40 rounded-2xl border border-white/10">
                    <p class="text-xs text-gray-400 font-bold uppercase tracking-widest mb-3">
                        <i class="fas fa-key mr-1 text-yellow-400"></i>SSH Access
                    </p>
                    @if($serverIp)
                    <div class="flex items-center justify-between p-3 bg-black/50 rounded-xl mb-2">
                        <code class="text-green-400 text-sm font-mono">ssh root@{{ $serverIp }}</code>
                        <button onclick="navigator.clipboard.writeText('ssh root@{{ $serverIp }}')"
                                class="text-gray-400 hover:text-white ml-3 flex-shrink-0" title="Copy">
                            <i class="fas fa-copy text-xs"></i>
                        </button>
                    </div>
                    @endif
                    <p class="text-[10px] text-gray-500 mt-2">Use your secure root password. Reset it from Server Actions if needed.</p>
                </div>

                {{-- Console Launch --}}
                @if($vpsId && $isRunning)
                <div class="text-center py-6"
                     x-data="{
                         loading: false,
                         openConsole() {
                             this.loading = true;
                             fetch('/console/session/{{ $selectedHosting->id }}', {
                                 method: 'POST',
                                 headers: {
                                     'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'),
                                     'Content-Type': 'application/json'
                                 }
                             })
                             .then(r => r.json())
                             .then(data => {
                                 this.loading = false;
                                 if (data.success && data.console_url) {
                                     window.open(data.console_url, '_blank', 'width=1024,height=768,scrollbars=no,toolbar=no');
                                 } else {
                                     alert('Console error: ' + (data.message || 'Unknown error. Check Proxmox password in admin settings.'));
                                 }
                             })
                             .catch(e => { this.loading = false; alert('Failed: ' + e.message); });
                         }
                     }">
                    <div class="w-20 h-20 rounded-full bg-green-500/20 flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-terminal text-green-400 text-3xl"></i>
                    </div>
                    <p class="text-gray-400 text-sm mb-6">Opens a secure, time-limited console session</p>
                    <button @click="openConsole()"
                            :disabled="loading"
                            class="px-8 py-4 rounded-2xl bg-green-500/20 text-green-400 border border-green-500/30 font-black uppercase tracking-wider text-sm hover:bg-green-500 hover:text-white transition-all inline-flex items-center gap-3 disabled:opacity-50">
                        <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-terminal'"></i>
                        <span x-text="loading ? 'Opening...' : 'Open Console'"></span>
                    </button>
                    <p class="text-xs text-gray-500 mt-3">
                        <i class="fas fa-info-circle mr-1"></i>
                        Credentials are kept server-side; nothing is exposed to the browser
                    </p>
                </div>

                @else
                <div class="text-center py-10">
                    <i class="fas fa-power-off text-5xl text-gray-600 mb-4 opacity-30"></i>
                    <p class="text-gray-400 font-bold text-lg">Server is not running</p>
                    <p class="text-xs text-gray-500 mt-2 mb-6">Start your server first to access the console</p>
                    @if(!$vpsId)
                    <p class="text-xs text-red-400">No VM linked to this hosting. Contact support.</p>
                    @endif
                </div>
                @endif

                {{-- Instructions --}}
                <div class="p-4 bg-yellow-500/5 border border-yellow-500/20 rounded-2xl">
                    <p class="text-xs text-yellow-400 font-bold mb-2"><i class="fas fa-info-circle mr-1"></i>Console Tips</p>
                    <ul class="text-xs text-gray-400 space-y-1">
                        <li>• Click inside console to capture keyboard input</li>
                        <li>• Use right-click menu for copy/paste</li>
                        <li>• Ctrl+Alt+Del sends to server (not your PC)</li>
                        <li>• Black screen = server booting, wait 30-60 seconds</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- DNS Tab Content --}}
    @elseif($activeHostingTab === 'dns')
    <div class="animate-in fade-in duration-300">
        <div class="glass rounded-3xl border border-white/10 p-8 mb-6">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-14 h-14 rounded-2xl bg-electric-blue/20 flex items-center justify-center">
                    <i class="fas fa-network-wired text-electric-blue text-xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-black text-white uppercase tracking-wider">Secondary DNS</h3>
                    <p class="text-sm text-gray-400">Manage your domain's DNS configuration</p>
                </div>
            </div>
            <div class="p-6 bg-white/5 rounded-2xl text-center">
                <i class="fas fa-globe text-4xl text-gray-600 mb-4"></i>
                <p class="text-gray-400 mb-4">No domains configured for Secondary DNS</p>
                <div class="flex flex-wrap justify-center gap-3">
                    <a href="{{ route('client.domains.my-domains') }}" class="inline-flex items-center px-6 py-3 rounded-xl bg-electric-blue/20 text-electric-blue border border-electric-blue/30 font-black uppercase tracking-wider text-xs hover:bg-electric-blue hover:text-dark transition-all">
                        <i class="fas fa-plus mr-2"></i>Register New Domain
                    </a>
                    <button onclick="document.getElementById('connect-domain-modal').classList.remove('hidden')" class="inline-flex items-center px-6 py-3 rounded-xl bg-green-500/20 text-green-500 border border-green-500/30 font-black uppercase tracking-wider text-xs hover:bg-green-500 hover:text-dark transition-all">
                        <i class="fas fa-link mr-2"></i>Connect External Domain
                    </button>
                </div>

                {{-- Connect External Domain Modal - Hostinger/OVH Style --}}
                <div id="connect-domain-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm overflow-y-auto">
                    <div class="glass rounded-3xl border border-white/20 p-6 max-w-lg w-full shadow-2xl transform transition-all my-8 max-h-[90vh] overflow-y-auto">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-xl bg-green-500/20 flex items-center justify-center">
                                    <i class="fas fa-link text-green-500 text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-black text-white uppercase tracking-wider">Connect External Domain</h3>
                                    <p class="text-xs text-gray-400">Link your existing domain to BelieVoo</p>
                                </div>
                            </div>
                            <button onclick="document.getElementById('connect-domain-modal').classList.add('hidden')" class="w-10 h-10 rounded-xl bg-white/5 text-gray-400 hover:text-white hover:bg-white/10 transition-all flex items-center justify-center">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        {{-- Step 1: Domain Input --}}
                        <div class="space-y-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Domain Name</label>
                                <input type="text" id="external-domain-name" placeholder="example.com" class="w-full px-4 py-3 rounded-xl bg-dark-300/50 border border-white/10 text-white placeholder-gray-500 focus:outline-none focus:border-green-500/50 transition-all">
                                <p class="text-xs text-gray-500 mt-1">Enter the domain you purchased from another registrar</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Current Nameservers (Optional)</label>
                                <input type="text" id="external-domain-ns" placeholder="ns1.godaddy.com, ns2.godaddy.com" class="w-full px-4 py-3 rounded-xl bg-dark-300/50 border border-white/10 text-white placeholder-gray-500 focus:outline-none focus:border-green-500/50 transition-all">
                                <p class="text-xs text-gray-500 mt-1">This helps us detect your current registrar</p>
                            </div>
                        </div>

                        {{-- Step 2: Instructions --}}
                        <div class="p-4 rounded-xl bg-green-500/10 border border-green-500/30 mb-6">
                            <div class="flex items-start gap-3">
                                <i class="fas fa-info-circle text-green-500 mt-0.5"></i>
                                <div>
                                    <h4 class="text-green-500 font-bold text-sm mb-1">How it works</h4>
                                    <ol class="text-xs text-gray-400 space-y-1 list-decimal list-inside">
                                        <li>Enter your domain name above</li>
                                        <li>Click "Connect Domain" to add it</li>
                                        <li>Update nameservers at your registrar to:
                                            <div class="mt-2 p-2 bg-dark-300/50 rounded-lg font-mono text-green-400 text-xs">
                                                ns1.believoo.com<br>ns2.believoo.com
                                            </div>
                                        </li>
                                        <li>DNS management will be active in 24-48 hours</li>
                                    </ol>
                                </div>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex gap-3">
                            <button onclick="document.getElementById('connect-domain-modal').classList.add('hidden')" class="flex-1 px-6 py-3 rounded-xl bg-white/5 text-gray-400 font-bold uppercase tracking-wider text-xs hover:bg-white/10 hover:text-white transition-all border border-white/10">
                                Cancel
                            </button>
                            <button onclick="submitExternalDomain()" class="flex-1 px-6 py-3 rounded-xl bg-green-500/20 text-green-500 font-bold uppercase tracking-wider text-xs hover:bg-green-500 hover:text-dark transition-all border border-green-500/30">
                                <i class="fas fa-link mr-2"></i>Connect Domain
                            </button>
                        </div>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-4">
                    <i class="fas fa-info-circle mr-1"></i>
                    Already have a domain from GoDaddy, Namecheap, or other registrar? Connect it here.
                </p>
            </div>
        </div>

        {{-- Nameservers Section - Hostinger/OVH Style --}}
        @php
            $believooNs = \App\Models\DnsRecord::BELIEVOO_NS ?? ['ns1.believoo.com', 'ns2.believoo.com'];
            $rawNs = $selectedHosting?->domain?->nameservers ?? [];
            // nameservers may be stored as JSON string — decode it
            $currentNs = is_string($rawNs) ? (json_decode($rawNs, true) ?? []) : (is_array($rawNs) ? $rawNs : []);
            $hasBelievooNs = false;
            foreach ($believooNs as $ns) {
                foreach ($currentNs as $current) {
                    if (str_contains(strtolower((string)$current), strtolower($ns))) {
                        $hasBelievooNs = true;
                        break 2;
                    }
                }
            }
            $needsNsUpdate = !empty($selectedHosting?->domain) && !$hasBelievooNs;
        @endphp

        <div class="glass rounded-3xl border border-white/10 overflow-hidden mb-6">
            <div class="p-6 border-b border-white/10 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-black text-white uppercase tracking-wider">Nameservers</h3>
                    <p class="text-sm text-gray-400">Your domain's current nameserver configuration</p>
                </div>
                <div class="flex items-center gap-2">
                    <button wire:click="openNsModal" class="px-3 py-1.5 rounded-lg bg-electric-blue/20 border border-electric-blue/30 text-electric-blue font-bold text-xs uppercase tracking-wider hover:bg-electric-blue/30 transition-all flex items-center gap-1.5">
                        <i class="fas fa-pen text-[10px]"></i> Change
                    </button>
                    @if($needsNsUpdate)
                        <div class="px-4 py-2 bg-amber-500/20 border border-amber-500/30 rounded-xl">
                            <span class="text-amber-500 font-bold text-xs uppercase tracking-wider">
                                <i class="fas fa-exclamation-triangle mr-2"></i>Action Required
                            </span>
                        </div>
                    @endif
                </div>
            </div>
            <div class="p-6">
                @if($needsNsUpdate)
                    <div class="mb-6 p-4 bg-amber-500/10 border border-amber-500/30 rounded-2xl">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-xl bg-amber-500/20 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-amber-500"></i>
                            </div>
                            <div>
                                <h4 class="text-amber-500 font-bold mb-1">Update Required</h4>
                                <p class="text-gray-400 text-sm mb-3">
                                    Your domain is not using BelieVoo nameservers. Update your nameservers at your registrar to use BelieVoo DNS for full management.
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($believooNs as $ns)
                                        <span class="px-3 py-1 bg-electric-blue/20 text-electric-blue rounded-lg text-sm font-mono">{{ $ns }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs text-gray-500 uppercase tracking-wider">
                                <th class="pb-3">Nameserver</th>
                                <th class="pb-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            @if(!empty($currentNs))
                                @foreach($currentNs as $ns)
                                    <tr class="border-t border-white/5">
                                        <td class="py-4 text-white font-mono">{{ $ns }}</td>
                                        <td class="py-4">
                                            @php
                                                $isBelievooNs = false;
                                                foreach ($believooNs as $bns) {
                                                    if (str_contains(strtolower($ns), strtolower($bns))) {
                                                        $isBelievooNs = true;
                                                        break;
                                                    }
                                                }
                                            @endphp
                                            @if($isBelievooNs)
                                                <span class="px-3 py-1 bg-green-500/20 text-green-500 rounded-lg text-xs font-bold">BelieVoo</span>
                                            @else
                                                <span class="px-3 py-1 bg-gray-500/20 text-gray-400 rounded-lg text-xs">External</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr class="border-t border-white/5">
                                    <td colspan="2" class="py-4 text-gray-400 text-center">
                                        No nameservers configured
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                @if(!empty($believooNs) && !$needsNsUpdate)
                    <div class="mt-4 p-4 bg-green-500/10 border border-green-500/30 rounded-2xl">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-check-circle text-green-500"></i>
                            <span class="text-green-500 text-sm">Your domain is using BelieVoo nameservers. DNS management is active.</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- DNS Records --}}
        <div class="glass rounded-3xl border border-white/10 overflow-hidden">
            <div class="p-6 border-b border-white/10">
                <h3 class="text-sm font-black text-white uppercase tracking-widest">DNS Records</h3>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs text-gray-500 uppercase tracking-wider">
                                <th class="pb-3">Type</th>
                                <th class="pb-3">Name</th>
                                <th class="pb-3">Value</th>
                                <th class="pb-3">TTL</th>
                                <th class="pb-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            @php
                                // Get real DNS records from database for this hosting's domain
                                $dnsRecords = collect();
                                if ($selectedHosting && $selectedHosting->domain) {
                                    $dnsRecords = \App\Models\DnsRecord::byDomain($selectedHosting->domain->id)
                                        ->active()
                                        ->orderBy('record_type')
                                        ->orderBy('name')
                                        ->get();
                                }
                                // If no records found, show the hosting IP as fallback
                                $fallbackIp = $serverDetails['ip'] ?? $selectedHosting->server_ip ?? null;
                            @endphp

                            @if($dnsRecords->count() > 0)
                                @foreach($dnsRecords as $record)
                                    <tr class="border-t border-white/5">
                                        <td class="py-4 text-electric-blue font-bold">{{ $record->record_type }}</td>
                                        <td class="py-4 text-white">{{ $record->name }}</td>
                                        <td class="py-4 text-gray-400">{{ $record->getDisplayValueAttribute() }}</td>
                                        <td class="py-4 text-gray-400">{{ $record->ttl }}</td>
                                        <td class="py-4">
                                            <button class="text-gray-400 hover:text-electric-blue" aria-label="Edit DNS record">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @elseif($fallbackIp)
                                {{-- Show fallback A record if no DNS records found but IP exists --}}
                                <tr class="border-t border-white/5">
                                    <td class="py-4 text-electric-blue font-bold">A</td>
                                    <td class="py-4 text-white">{{ $selectedHosting->server_hostname ?? '@' }}</td>
                                    <td class="py-4 text-gray-400">{{ $fallbackIp }}</td>
                                    <td class="py-4 text-gray-400">3600</td>
                                    <td class="py-4">
                                        <button class="text-gray-400 hover:text-electric-blue" aria-label="Edit DNS record">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                            @else
                                <tr class="border-t border-white/5">
                                    <td colspan="5" class="py-8 text-center">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-database text-3xl text-gray-600 mb-3"></i>
                                            <p class="text-gray-400 mb-4">No DNS records found for this domain</p>
                                            <a href="#" onclick="fetchDnsRecords({{ $selectedHosting?->domain?->id ?? 0 }})" class="px-4 py-2 bg-electric-blue/20 text-electric-blue rounded-lg text-sm font-medium hover:bg-electric-blue hover:text-white transition-all">
                                                <i class="fas fa-sync mr-2"></i>Fetch from Provider
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                {{-- Add DNS Record Button --}}
                @if($selectedHosting && $selectedHosting->domain)
                    <div class="mt-6 flex items-center justify-between">
                        <p class="text-sm text-gray-400">
                            <i class="fas fa-info-circle mr-2"></i>
                            Manage A, AAAA, CNAME, MX, TXT, and other DNS records
                        </p>
                        <button onclick="openAddDnsRecordModal({{ $selectedHosting->domain->id }})" class="px-6 py-3 rounded-xl bg-electric-blue/20 text-electric-blue border border-electric-blue/30 font-black uppercase tracking-wider text-xs hover:bg-electric-blue hover:text-dark transition-all">
                            <i class="fas fa-plus mr-2"></i>Add DNS Record
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Backup Tab Content --}}
    @elseif($activeHostingTab === 'backup')
    <div class="animate-in fade-in duration-300">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Automated Backup --}}
            <div class="glass rounded-3xl border border-white/10 p-6">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-14 h-14 rounded-2xl bg-green-500/20 flex items-center justify-center">
                        <i class="fas fa-shield-alt text-green-500 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-white uppercase tracking-wider">Automated Backup</h3>
                        <p class="text-sm text-gray-400">Daily automated snapshots</p>
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl">
                        <span class="text-sm text-gray-400">Status</span>
                        <span class="px-3 py-1 rounded-full text-xs font-black uppercase {{ $selectedHosting->automated_backup ? 'bg-green-500/20 text-green-500' : 'bg-gray-500/20 text-gray-500' }}">
                            {{ $selectedHosting->automated_backup ? 'Enabled' : 'Disabled' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl">
                        <span class="text-sm text-gray-400">Schedule</span>
                        <span class="text-sm text-white font-bold">Daily at 02:00 UTC</span>
                    </div>

                    {{-- Retention Period Selection - Hostinger Style --}}
                    <div class="p-4 bg-white/5 rounded-2xl">
                        <span class="text-sm text-gray-400 block mb-3">Retention Period</span>
                        <div class="grid grid-cols-3 gap-2">
                            @php
                                $retentionOptions = [
                                    1 => ['label' => '1 Day', 'icon' => 'fa-calendar-day'],
                                    7 => ['label' => '7 Days', 'icon' => 'fa-calendar-week'],
                                    30 => ['label' => '30 Days', 'icon' => 'fa-calendar-alt'],
                                ];
                                $currentRetention = $selectedHosting->backup_retention_days ?? 7;
                            @endphp
                            @foreach($retentionOptions as $days => $option)
                                <button wire:click="setBackupRetention({{ $days }})" wire:loading.attr="disabled"
                                    class="flex flex-col items-center justify-center p-3 rounded-xl border transition-all {{ $currentRetention == $days ? 'bg-electric-blue/20 border-electric-blue text-electric-blue' : 'bg-white/5 border-white/10 text-gray-400 hover:border-white/30 hover:text-white' }}">
                                    <i class="fas {{ $option['icon'] }} mb-1 {{ $currentRetention == $days ? 'text-electric-blue' : 'text-gray-500' }}"></i>
                                    <span class="text-xs font-bold {{ $currentRetention == $days ? 'text-electric-blue' : 'text-gray-400' }}">{{ $option['label'] }}</span>
                                </button>
                            @endforeach
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            Backups older than {{ $currentRetention }} day{{ $currentRetention > 1 ? 's' : '' }} will be automatically deleted
                        </p>
                    </div>

                    {{-- Backup Statistics --}}
                    @php
                        $totalBackups = \App\Models\VpsSnapshot::where('user_id', Auth::id())
                            ->whereHas('vm', function($q) {
                                $q->where('vmid', $this->selectedHosting->vps_id ?? 0);
                            })
                            ->where('type', 'auto')
                            ->count();
                    @endphp
                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl">
                        <span class="text-sm text-gray-400">Total Backups</span>
                        <span class="text-sm text-white font-bold">{{ $totalBackups }}</span>
                    </div>

                    <button wire:click="toggleAutomatedBackup" wire:loading.attr="disabled"
                        class="w-full py-3 rounded-xl {{ $selectedHosting->automated_backup ? 'bg-red-500/20 text-red-500 border border-red-500/30 hover:bg-red-500 hover:text-white' : 'bg-electric-blue/20 text-electric-blue border border-electric-blue/30 hover:bg-electric-blue hover:text-dark' }} font-black uppercase tracking-wider text-xs transition-all flex items-center justify-center gap-2">
                        <i class="fas {{ $selectedHosting->automated_backup ? 'fa-pause' : 'fa-play' }} mr-2"></i>
                        {{ $selectedHosting->automated_backup ? 'Disable Backups' : 'Enable Backups' }}
                    </button>
                </div>
            </div>

            {{-- Manual Snapshots --}}
            <div class="glass rounded-3xl border border-white/10 overflow-hidden">
                <div class="p-6 border-b border-white/10">
                    <h3 class="text-sm font-black text-white uppercase tracking-widest">Manual Snapshots</h3>
                </div>
                <div class="p-6">
                    @php
                        $snapshots = \App\Models\VpsSnapshot::where('user_id', Auth::id())
                            ->whereHas('vm', function($q) {
                                $q->where('vmid', $this->selectedHosting->vps_id ?? 0);
                            })
                            ->orderBy('created_at', 'desc')
                            ->get();
                    @endphp
                    @if($snapshots->count() > 0)
                        <div class="space-y-3 mb-4">
                            @foreach($snapshots as $snapshot)
                                <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl">
                                    <div class="flex items-center gap-3">
                                        <i class="fas fa-camera text-electric-blue"></i>
                                        <div>
                                            <p class="text-sm font-bold text-white">{{ $snapshot->name }}</p>
                                            <p class="text-xs text-gray-400">{{ $snapshot->created_at->format('M d, Y H:i') }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-1 rounded-lg text-xs font-bold {{ $snapshot->status === 'completed' ? 'bg-green-500/20 text-green-500' : ($snapshot->status === 'failed' ? 'bg-red-500/20 text-red-500' : 'bg-gray-500/20 text-gray-500') }}">
                                            {{ ucfirst($snapshot->status) }}
                                        </span>
                                        <button wire:click="restoreSnapshot({{ $snapshot->id }})" wire:loading.attr="disabled"
                                            class="p-2 rounded-lg bg-white/5 text-gray-400 hover:text-electric-blue hover:bg-electric-blue/10 transition" title="Restore">
                                            <i class="fas fa-undo-alt text-xs"></i>
                                        </button>
                                        <button wire:click="deleteSnapshot({{ $snapshot->id }})" wire:loading.attr="disabled"
                                            wire:confirm="Are you sure you want to delete this snapshot?"
                                            class="p-2 rounded-lg bg-white/5 text-gray-400 hover:text-red-400 hover:bg-red-500/10 transition" title="Delete">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <i class="fas fa-camera text-4xl text-gray-600 mb-4"></i>
                            <p class="text-gray-400 mb-4">No manual snapshots created yet</p>
                        </div>
                    @endif
                    <button wire:click="createSnapshot" wire:loading.attr="disabled"
                        class="w-full py-3 rounded-xl bg-electric-blue/20 text-electric-blue border border-electric-blue/30 font-black uppercase tracking-wider text-xs hover:bg-electric-blue hover:text-dark transition-all">
                        <i class="fas fa-plus mr-2"></i>Create Snapshot
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Disks Tab Content --}}
    @elseif($activeHostingTab === 'disks')
    <div class="animate-in fade-in duration-300">
        <div class="glass rounded-3xl border border-white/10 overflow-hidden mb-6">
            <div class="p-6 border-b border-white/10">
                <h3 class="text-sm font-black text-white uppercase tracking-widest">Additional Disks</h3>
            </div>
            <div class="p-6">
                <div class="text-center py-8">
                    <i class="fas fa-hdd text-4xl text-gray-600 mb-4"></i>
                    <p class="text-gray-400 mb-4">No additional disks attached</p>
                    <button class="px-6 py-3 rounded-xl bg-electric-blue/20 text-electric-blue border border-electric-blue/30 font-black uppercase tracking-wider text-xs hover:bg-electric-blue hover:text-dark transition-all">
                        <i class="fas fa-plus mr-2"></i>Add Disk
                    </button>
                </div>
            </div>
        </div>

        {{-- Current Storage --}}
        <div class="glass rounded-3xl border border-white/10 p-6">
            <h3 class="text-sm font-black text-white uppercase tracking-widest mb-4">Current Storage</h3>
            <div class="space-y-4">
                <div class="p-4 bg-white/5 rounded-2xl">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-gray-400">Primary Disk</span>
                        <span class="text-sm text-white font-bold">{{ $selectedHosting->storage_size ?? 'N/A' }}</span>
                    </div>
                    @if($selectedHosting->storage_used)
                        <div class="h-2 w-full bg-white/10 rounded-full overflow-hidden mb-2">
                            <div class="h-full bg-gradient-to-r from-electric-blue to-electric-violet rounded-full" style="width: {{ $selectedHosting->getStoragePercentage() }}%"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500">
                            <span>{{ $selectedHosting->storage_used }} used</span>
                            <span>{{ $selectedHosting->getStorageRemaining() }} free</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Databases Tab Content --}}
    @elseif($activeHostingTab === 'databases')
    <div class="animate-in fade-in duration-300">
        <div class="glass rounded-3xl border border-white/10 overflow-hidden">
            <div class="p-6 border-b border-white/10 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-electric-blue/20 flex items-center justify-center">
                        <i class="fas fa-database text-electric-blue"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-white uppercase tracking-widest">Databases</h3>
                        <p class="text-xs text-gray-400">Manage your server databases</p>
                    </div>
                </div>
                <button class="px-4 py-2 rounded-xl bg-electric-blue/20 text-electric-blue border border-electric-blue/30 font-black uppercase tracking-wider text-xs hover:bg-electric-blue hover:text-dark transition-all">
                    <i class="fas fa-plus mr-2"></i>Create Database
                </button>
            </div>
            <div class="p-6">
                <div class="text-center py-12">
                    <i class="fas fa-database text-5xl text-gray-600 mb-4"></i>
                    <p class="text-gray-400 mb-2">No databases created yet</p>
                    <p class="text-xs text-gray-500 mb-6">Create MySQL, PostgreSQL, or MongoDB databases</p>
                    <div class="flex gap-3 justify-center">
                        <button class="px-4 py-2 rounded-xl bg-white/5 text-gray-400 border border-white/10 font-black uppercase tracking-wider text-xs hover:bg-white/10 hover:text-white transition-all">
                            <i class="fab fa-mysql mr-2"></i>MySQL
                        </button>
                        <button class="px-4 py-2 rounded-xl bg-white/5 text-gray-400 border border-white/10 font-black uppercase tracking-wider text-xs hover:bg-white/10 hover:text-white transition-all">
                            <i class="fas fa-elephant mr-2"></i>PostgreSQL
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Monitoring Tab Content --}}
    @elseif($activeHostingTab === 'monitoring')
    <div class="animate-in fade-in duration-300">
        @php
            $cpuPercent  = $serverDetails['cpu_percent'] ?? 0;
            $memPercent  = $serverDetails['mem_percent'] ?? 0;
            $memUsedGb   = $serverDetails['mem_used_gb'] ?? 0;
            $memTotalGb  = $serverDetails['mem_total_gb'] ?? 0;
            $diskPercent = $serverDetails['disk_percent'] ?? 0;
            $diskUsedGb  = $serverDetails['disk_used_gb'] ?? 0;
            $diskTotalGb = $serverDetails['disk_total_gb'] ?? 0;
            $netin       = $serverDetails['netin'] ?? 0;
            $netout      = $serverDetails['netout'] ?? 0;
            $uptime      = $serverDetails['uptime'] ?? 0;
            $uptimeHours = $uptime > 0 ? floor($uptime / 3600) . 'h ' . floor(($uptime % 3600) / 60) . 'm' : 'N/A';
        @endphp

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- CPU Usage --}}
            <div class="glass rounded-3xl border border-white/10 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-black text-white uppercase tracking-widest">CPU Usage</h3>
                    <span class="text-xs font-bold {{ $cpuPercent > 80 ? 'text-red-400' : ($cpuPercent > 60 ? 'text-yellow-400' : 'text-electric-blue') }}">{{ $cpuPercent }}%</span>
                </div>
                <div class="relative h-4 bg-white/10 rounded-full overflow-hidden mb-3">
                    <div class="h-full rounded-full transition-all duration-700 {{ $cpuPercent > 80 ? 'bg-red-500' : ($cpuPercent > 60 ? 'bg-yellow-500' : 'bg-electric-blue') }}" style="width: {{ $cpuPercent }}%"></div>
                </div>
                <div class="flex justify-between text-xs text-gray-500">
                    <span>{{ $selectedHosting->cpu_cores ?? 'N/A' }} vCores allocated</span>
                    <span>{{ $cpuPercent }}% used</span>
                </div>
                <div class="mt-4 p-3 bg-white/5 rounded-xl">
                    <span class="text-xs text-gray-400">Uptime: <span class="text-white font-bold">{{ $uptimeHours }}</span></span>
                </div>
            </div>

            {{-- Memory Usage --}}
            <div class="glass rounded-3xl border border-white/10 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-black text-white uppercase tracking-widest">Memory Usage</h3>
                    <span class="text-xs font-bold {{ $memPercent > 80 ? 'text-red-400' : ($memPercent > 60 ? 'text-yellow-400' : 'text-electric-blue') }}">{{ $memPercent }}%</span>
                </div>
                <div class="relative h-4 bg-white/10 rounded-full overflow-hidden mb-3">
                    <div class="h-full rounded-full transition-all duration-700 {{ $memPercent > 80 ? 'bg-red-500' : ($memPercent > 60 ? 'bg-yellow-500' : 'bg-electric-blue') }}" style="width: {{ $memPercent }}%"></div>
                </div>
                <div class="flex justify-between text-xs text-gray-500">
                    <span>{{ $memUsedGb }} GB used</span>
                    <span>{{ $memTotalGb }} GB total</span>
                </div>
            </div>

            {{-- Disk Usage --}}
            <div class="glass rounded-3xl border border-white/10 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-black text-white uppercase tracking-widest">Disk Usage</h3>
                    <span class="text-xs font-bold {{ $diskPercent > 80 ? 'text-red-400' : ($diskPercent > 60 ? 'text-yellow-400' : 'text-yellow-400') }}">{{ $diskPercent }}%</span>
                </div>
                <div class="relative h-4 bg-white/10 rounded-full overflow-hidden mb-3">
                    <div class="h-full rounded-full transition-all duration-700 {{ $diskPercent > 80 ? 'bg-red-500' : 'bg-yellow-500' }}" style="width: {{ $diskPercent }}%"></div>
                </div>
                <div class="flex justify-between text-xs text-gray-500">
                    <span>{{ $diskUsedGb }} GB used</span>
                    <span>{{ $diskTotalGb }} GB total</span>
                </div>
            </div>

            {{-- Network Traffic --}}
            <div class="glass rounded-3xl border border-white/10 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-black text-white uppercase tracking-widest">Network I/O</h3>
                    <span class="text-xs text-gray-500">Total since boot</span>
                </div>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-3 bg-white/5 rounded-xl">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-arrow-down text-electric-blue text-sm"></i>
                            <span class="text-xs text-gray-400">Inbound</span>
                        </div>
                        <span class="text-sm font-bold text-white">
                            @php
                                if ($netin > 1073741824) echo round($netin / 1073741824, 2) . ' GB';
                                elseif ($netin > 1048576) echo round($netin / 1048576, 2) . ' MB';
                                else echo round($netin / 1024, 2) . ' KB';
                            @endphp
                        </span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-white/5 rounded-xl">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-arrow-up text-green-500 text-sm"></i>
                            <span class="text-xs text-gray-400">Outbound</span>
                        </div>
                        <span class="text-sm font-bold text-white">
                            @php
                                if ($netout > 1073741824) echo round($netout / 1073741824, 2) . ' GB';
                                elseif ($netout > 1048576) echo round($netout / 1048576, 2) . ' MB';
                                else echo round($netout / 1024, 2) . ' KB';
                            @endphp
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Refresh Button --}}
        <div class="mt-6 flex justify-center">
            <button wire:click="refreshServerStatus" class="px-6 py-3 rounded-xl bg-electric-blue/20 text-electric-blue border border-electric-blue/30 font-black uppercase tracking-wider text-xs hover:bg-electric-blue hover:text-dark transition-all">
                <i class="fas fa-sync-alt mr-2 {{ $isLoadingServer ? 'animate-spin' : '' }}"></i>Refresh Metrics
            </button>
        </div>
    </div>

    {{-- Migration Tab Content --}}
    @elseif($activeHostingTab === 'migration')
    <div class="animate-in fade-in duration-300"
         x-data="{
             pollInterval: null,
             
             init() {
                 const activeMigration = @js($activeMigration);
                 if (activeMigration && activeMigration.status === 'migrating') {
                     this.startPolling();
                 }
             },
             
             startPolling() {
                 if (this.pollInterval) clearInterval(this.pollInterval);
                 this.pollInterval = setInterval(() => {
                     @this.refreshMigrationProgress();
                 }, 3000);
             },
             
             stopPolling() {
                 if (this.pollInterval) {
                     clearInterval(this.pollInterval);
                     this.pollInterval = null;
                 }
             }
         }"
         x-on:migration-started.window="startPolling()"
         x-on:migration-completed.window="stopPolling()">
        @php
            $currentNode  = $serverDetails['node'] ?? config('proxmox.node', 'ns548195');
            $proxmoxVm = \App\Models\ProxmoxVm::where('vmid', $selectedHosting->vps_id ?? 0)->first();
            $currentNodeModel = \App\Models\ProxmoxNode::where('name', $currentNode)->first();
            $currentLocation = $currentNodeModel?->display_name ?? ($selectedHosting->datacenter_location ?? 'Singapore DC-1');
        @endphp

        <div class="glass rounded-3xl border border-white/10 overflow-hidden mb-6">
            <div class="p-6 border-b border-white/10 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-black text-white uppercase tracking-widest">
                        <i class="fas fa-exchange-alt mr-2 text-electric-blue"></i>VM Migration
                    </h3>
                    <p class="text-xs text-gray-400 mt-1">Move your VM to a different datacenter location with zero downtime</p>
                </div>
                @if($activeMigration && in_array($activeMigration['status'] ?? '', ['migrating', 'pending']))
                    <span class="px-3 py-1 rounded-full text-xs font-black bg-yellow-500/20 text-yellow-500 animate-pulse">
                        <i class="fas fa-spinner fa-spin mr-1"></i> Migrating...
                    </span>
                @endif
            </div>

            <div class="p-6 space-y-4">
                {{-- Active Migration Progress --}}
                @if($activeMigration && in_array($activeMigration['status'] ?? '', ['migrating', 'pending']))
                <div class="p-4 bg-yellow-500/10 border border-yellow-500/30 rounded-2xl">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-bold text-white">
                            <i class="fas fa-sync fa-spin mr-2 text-yellow-500"></i>
                            Migration in Progress
                        </p>
                        <span class="text-xs text-yellow-400 font-mono">{{ $migrationProgress }}%</span>
                    </div>
                    <div class="w-full h-2 bg-white/10 rounded-full overflow-hidden mb-3">
                        <div class="h-full bg-gradient-to-r from-yellow-500 to-electric-blue rounded-full transition-all duration-500"
                             style="width: {{ $migrationProgress }}%"></div>
                    </div>
                    <p class="text-xs text-gray-400">{{ $migrationMessage ?: 'Migrating VM to target node...' }}</p>
                    <div class="mt-3 flex items-center gap-4 text-xs">
                        <span class="text-gray-500"><i class="fas fa-server mr-1"></i>From: {{ $activeMigration['source_node'] ?? $currentNode }}</span>
                        <i class="fas fa-arrow-right text-electric-blue"></i>
                        <span class="text-electric-blue"><i class="fas fa-server mr-1"></i>To: {{ $activeMigration['target_node'] ?? '...' }}</span>
                    </div>
                </div>
                @endif

                {{-- Migration Completed --}}
                @if($activeMigration && ($activeMigration['status'] ?? '') === 'completed')
                <div class="p-4 bg-green-500/10 border border-green-500/30 rounded-2xl">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-check-circle text-green-500 text-2xl"></i>
                        <div>
                            <p class="text-sm font-bold text-white">Migration Completed!</p>
                            <p class="text-xs text-gray-400">Your VM has been successfully migrated to {{ $activeMigration['target_node'] }}</p>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Current Location (Branded) --}}
                <div class="p-4 bg-white/5 rounded-2xl flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-electric-blue/20 flex items-center justify-center">
                            <i class="fas fa-map-marker-alt text-electric-blue"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-1">Current Location</p>
                            <p class="text-sm font-bold text-white">{{ $currentLocation }}</p>
                            <p class="text-[10px] text-gray-500">Node: {{ $currentNode }}</p>
                        </div>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-black bg-green-500/20 text-green-500">Active</span>
                </div>

                {{-- Start Migration Button --}}
                @if(!$activeMigration || !in_array($activeMigration['status'] ?? '', ['migrating', 'pending']))
                <div class="text-center py-6">
                    <button wire:click="openMigrationModal"
                            wire:loading.attr="disabled"
                            class="px-8 py-4 rounded-2xl bg-gradient-to-r from-electric-blue to-purple-600 text-white font-black uppercase tracking-wider text-sm hover:shadow-lg hover:shadow-electric-blue/30 transition-all transform hover:-translate-y-1">
                        <i class="fas fa-exchange-alt mr-2"></i> Start VM Migration
                    </button>
                    <p class="text-xs text-gray-500 mt-3">Live migration with zero downtime</p>
                </div>
                @endif

                {{-- Migration Benefits Info --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-4 bg-green-500/5 border border-green-500/20 rounded-2xl text-center">
                        <i class="fas fa-bolt text-green-500 text-xl mb-2"></i>
                        <p class="text-xs font-bold text-white">Zero Downtime</p>
                        <p class="text-[10px] text-gray-500">Live migration keeps your site online</p>
                    </div>
                    <div class="p-4 bg-electric-blue/5 border border-electric-blue/20 rounded-2xl text-center">
                        <i class="fas fa-network-wired text-electric-blue text-xl mb-2"></i>
                        <p class="text-xs font-bold text-white">Same IP</p>
                        <p class="text-[10px] text-gray-500">IP address remains unchanged</p>
                    </div>
                    <div class="p-4 bg-purple-500/5 border border-purple-500/20 rounded-2xl text-center">
                        <i class="fas fa-clock text-purple-500 text-xl mb-2"></i>
                        <p class="text-xs font-bold text-white">1-5 Minutes</p>
                        <p class="text-[10px] text-gray-500">Fast transfer depending on disk</p>
                    </div>
                </div>

                {{-- Migration Info --}}
                <div class="p-4 bg-electric-blue/5 border border-electric-blue/20 rounded-2xl">
                    <p class="text-xs text-electric-blue font-bold mb-2"><i class="fas fa-info-circle mr-1"></i>Migration Info</p>
                    <ul class="text-xs text-gray-400 space-y-1">
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check text-green-500 mt-0.5"></i>
                            <span>Live migration - VM stays running during transfer</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check text-green-500 mt-0.5"></i>
                            <span>No data loss - 100% safe migration process</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check text-green-500 mt-0.5"></i>
                            <span>IP address remains the same after migration</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-exclamation-triangle text-yellow-500 mt-0.5"></i>
                            <span>Brief network pause (1-5 seconds) may occur</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Migration Modal --}}
        @if($showMigrationModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
             wire:click.self="closeMigrationModal">
            <div class="glass-premium rounded-3xl border border-white/20 w-full max-w-2xl max-h-[90vh] overflow-y-auto"
                 @click.stop>
                <div class="p-6 border-b border-white/10 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-black text-white uppercase tracking-wider">
                            <i class="fas fa-exchange-alt mr-2 text-electric-blue"></i>Select Destination
                        </h3>
                        <p class="text-xs text-gray-400 mt-1">Choose a target node for VM migration</p>
                    </div>
                    <button wire:click="closeMigrationModal" class="text-gray-400 hover:text-white transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    @if($isLoadingMigration)
                    <div class="text-center py-10">
                        <i class="fas fa-circle-notch fa-spin text-3xl text-electric-blue mb-4"></i>
                        <p class="text-gray-400">Loading available nodes...</p>
                    </div>
                    @elseif(empty($migrationNodes))
                    <div class="text-center py-10">
                        <i class="fas fa-server text-4xl text-gray-600 mb-4"></i>
                        <p class="text-gray-400 font-bold">No available nodes</p>
                        <p class="text-xs text-gray-500 mt-2">All nodes are currently at capacity or unavailable</p>
                    </div>
                    @else
                    <div class="space-y-3">
                        @foreach($migrationNodes as $node)
                        <div class="p-4 bg-white/5 rounded-2xl border {{ $selectedTargetNode === $node['name'] ? 'border-electric-blue bg-electric-blue/10' : 'border-white/10' }} 
                                    hover:border-electric-blue/50 transition-all cursor-pointer"
                             wire:click="$set('selectedTargetNode', '{{ $node['name'] }}')">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl {{ $selectedTargetNode === $node['name'] ? 'bg-electric-blue' : 'bg-white/10' }} flex items-center justify-center">
                                        <i class="fas fa-server {{ $selectedTargetNode === $node['name'] ? 'text-dark' : 'text-electric-blue' }}"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-white">{{ $node['name'] }}</p>
                                        <p class="text-xs text-gray-400">
                                            CPU: {{ $node['cpu'] }}% • RAM: {{ $node['memory_percent'] }}% • Disk: {{ $node['disk_percent'] }}%
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-green-400 font-bold">{{ $node['free_storage_gb'] }} GB Free</p>
                                    <p class="text-[10px] text-gray-500">Uptime: {{ $node['uptime'] }}</p>
                                </div>
                            </div>
                            @if($selectedTargetNode === $node['name'])
                            <div class="mt-3 pt-3 border-t border-white/10 flex items-center gap-2 text-xs text-electric-blue">
                                <i class="fas fa-check-circle"></i>
                                <span>Selected as target node</span>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>

                    {{-- Selected Node Info --}}
                    @if($selectedTargetNode)
                    <div class="p-4 bg-yellow-500/10 border border-yellow-500/30 rounded-2xl">
                        <p class="text-xs text-yellow-400 font-bold mb-2">
                            <i class="fas fa-exclamation-triangle mr-1"></i>Migration Warning
                        </p>
                        <p class="text-xs text-gray-400">
                            Migrating to <strong class="text-white">{{ $selectedTargetNode }}</strong>. 
                            This process will take 1-5 minutes depending on disk size. 
                            Your VM will remain online during migration.
                        </p>
                    </div>
                    @endif
                    @endif
                </div>

                <div class="p-6 border-t border-white/10 flex items-center justify-between">
                    <button wire:click="closeMigrationModal"
                            class="px-6 py-3 rounded-xl bg-white/5 text-gray-400 font-bold text-xs uppercase tracking-wider hover:bg-white/10 transition-all">
                        Cancel
                    </button>
                    <button wire:click="startVmMigration"
                            wire:loading.attr="disabled"
                            @disabled(!$selectedTargetNode)
                            class="px-6 py-3 rounded-xl bg-electric-blue text-dark font-black text-xs uppercase tracking-wider 
                                   hover:bg-white transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="startVmMigration">
                            <i class="fas fa-rocket mr-1"></i> Start Migration
                        </span>
                        <span wire:loading wire:target="startVmMigration">
                            <i class="fas fa-circle-notch fa-spin mr-1"></i> Starting...
                        </span>
                    </button>
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- Server Import Tab Content --}}
    @elseif($activeHostingTab === 'import')
    <div class="animate-in fade-in duration-300"
         x-data="{
             importProgress: @entangle('serverImportProgress'),
             importStatus: @entangle('serverImportStatus'),
             activeImport: @entangle('activeServerImport'),
             importMessage: @entangle('serverImportMessage'),
             syncLog: @entangle('serverImportSyncLog'),
             transferSpeed: @entangle('serverImportTransferSpeed'),
             eta: @entangle('serverImportEta'),
             activeTab: @entangle('serverImportActiveTab'),
             syncDirection: @entangle('serverImportSyncDirection'),
             sourceIp: '',
             sourcePassword: '',
             sourcePort: 22,
             testingConnection: false,
             connectionTested: false,
             connectionResult: null,
             showSyncForm: true,
             pollInterval: null,
             logLines: [],
             
             init() {
                 this.$nextTick(() => {
                     this.setupImportListener();
                 });
                 
                 // Check for active sync on load
                 if (this.activeImport && this.importStatus !== 'completed' && this.importStatus !== 'failed') {
                     this.showSyncForm = false;
                     this.startPolling();
                 }
                 
                 // Watch for sync log updates
                 this.$watch('syncLog', (value) => {
                     if (value) {
                         this.logLines = value.split('\n').filter(line => line.trim());
                         if (this.logLines.length > 20) {
                             this.logLines = this.logLines.slice(-20);
                         }
                     }
                 });
             },
             
             setupImportListener() {
                 const importId = this.activeImport?.id;
                 if (!importId) return;
                 
                 if (window.listenToServerImport) {
                     window.listenToServerImport(
                         importId,
                         (progress, message, data) => {
                             this.importProgress = progress;
                             if (message) this.importMessage = message;
                             if (data?.sync_log) this.syncLog = data.sync_log;
                             if (data?.transfer_speed) this.transferSpeed = data.transfer_speed;
                         },
                         (data) => {
                             this.importStatus = 'completed';
                             this.importProgress = 100;
                             this.stopPolling();
                             @this.refreshServerImportProgress();
                         },
                         (error) => {
                             this.importStatus = 'failed';
                             this.stopPolling();
                         }
                     );
                 }
             },
             
             startPolling() {
                 if (this.pollInterval) clearInterval(this.pollInterval);
                 this.pollInterval = setInterval(() => {
                     if (this.activeImport && ['pending', 'connecting', 'key_setup', 'syncing', 'verifying', 'config_updating'].includes(this.importStatus)) {
                         @this.refreshServerImportProgress();
                     } else if (['completed', 'failed'].includes(this.importStatus)) {
                         this.stopPolling();
                     }
                 }, 3000);
             },
             
             stopPolling() {
                 if (this.pollInterval) {
                     clearInterval(this.pollInterval);
                     this.pollInterval = null;
                 }
             },
             
             async testConnection() {
                 if (!this.sourceIp || !this.sourcePassword) {
                     alert('Please enter both IP and password');
                     return;
                 }
                 this.testingConnection = true;
                 try {
                     const response = await fetch('/api/server-imports/test-connection', {
                         method: 'POST',
                         headers: {
                             'Content-Type': 'application/json',
                             'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                         },
                         body: JSON.stringify({
                             source_ip: this.sourceIp,
                             source_root_password: this.sourcePassword,
                             source_port: parseInt(this.sourcePort)
                         })
                     });
                     const data = await response.json();
                     this.connectionResult = data;
                     this.connectionTested = true;
                 } catch (error) {
                     this.connectionResult = { success: false, message: 'Connection test failed: ' + error.message };
                     this.connectionTested = true;
                 }
                 this.testingConnection = false;
             },
             
             setSyncDirection(direction) {
                 this.syncDirection = direction;
                 this.activeTab = direction === 'pull' ? 'import' : 'export';
                 @this.set('serverImportSyncDirection', direction);
                 @this.set('serverImportActiveTab', this.activeTab);
             },
         }"
         x-on:import-started.window="showSyncForm = false; startPolling()"
         x-on:import-completed.window="stopPolling()">
        
        <div class="glass rounded-3xl border border-white/10 overflow-hidden mb-6">
            <div class="p-6 border-b border-white/10">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-black text-white uppercase tracking-widest">
                            <i class="fas fa-exchange-alt mr-2 text-orange-500"></i>Two-Way Data Sync
                        </h3>
                        <p class="text-xs text-gray-400 mt-1">Bidirectional sync between BelieVoo and external servers</p>
                    </div>
                    @if($activeServerImport && in_array($activeServerImport['status'] ?? '', ['pending', 'connecting', 'key_setup', 'syncing', 'verifying', 'config_updating']))
                        @php
                            $isPull = ($activeServerImport['sync_direction'] ?? 'pull') === 'pull';
                            $badgeText = $isPull ? 'Importing...' : 'Exporting...';
                            $badgeIcon = $isPull ? 'fa-cloud-download-alt' : 'fa-cloud-upload-alt';
                        @endphp
                        <span class="px-3 py-1 rounded-full text-xs font-black bg-orange-500/20 text-orange-500 animate-pulse">
                            <i class="fas {{ $badgeIcon }} mr-1"></i> {{ $badgeText }}
                        </span>
                    @endif
                </div>
                
                {{-- Import/Export Tabs --}}
                <div class="flex gap-2">
                    <button 
                        @click="setSyncDirection('pull')"
                        :class="activeTab === 'import' 
                            ? 'bg-orange-500 text-dark' 
                            : 'bg-white/10 text-gray-400 hover:text-white'"
                        class="px-4 py-2 rounded-xl font-bold text-xs uppercase tracking-wider transition-all flex items-center gap-2">
                        <i class="fas fa-cloud-download-alt"></i>
                        Import to BelieVoo
                    </button>
                    <button 
                        @click="setSyncDirection('push')"
                        :class="activeTab === 'export' 
                            ? 'bg-blue-500 text-white' 
                            : 'bg-white/10 text-gray-400 hover:text-white'"
                        class="px-4 py-2 rounded-xl font-bold text-xs uppercase tracking-wider transition-all flex items-center gap-2">
                        <i class="fas fa-cloud-upload-alt"></i>
                        Export to External
                    </button>
                </div>
            </div>

            <div class="p-6 space-y-6">
                {{-- Active Sync Progress with Live Log --}}
                @if($activeServerImport && in_array($activeServerImport['status'] ?? '', ['pending', 'connecting', 'key_setup', 'syncing', 'verifying', 'config_updating']))
                @php
                    $isPullActive = ($activeServerImport['sync_direction'] ?? 'pull') === 'pull';
                    $activeLabel = $isPullActive ? 'Import' : 'Export';
                    $activeColor = $isPullActive ? 'orange' : 'blue';
                @endphp
                <div class="p-4 bg-{{ $activeColor }}-500/10 border border-{{ $activeColor }}-500/30 rounded-2xl">
                    {{-- Header with Status --}}
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-bold text-white">
                            <i class="fas fa-sync fa-spin mr-2 text-{{ $activeColor }}-500"></i>
                            {{ $activeLabel }} in Progress
                        </p>
                        <div class="flex items-center gap-3">
                            @if($serverImportTransferSpeed)
                                <span class="text-xs text-green-400 font-mono">{{ $serverImportTransferSpeed }}</span>
                            @endif
                            @if($serverImportEta)
                                <span class="text-xs text-yellow-400 font-mono">ETA: {{ $serverImportEta }}</span>
                            @endif
                            <span class="text-xs text-{{ $activeColor }}-400 font-mono">{{ $serverImportProgress }}%</span>
                        </div>
                    </div>
                    
                    {{-- Progress Bar --}}
                    <div class="w-full h-2 bg-white/10 rounded-full overflow-hidden mb-3">
                        <div class="h-full bg-gradient-to-r from-{{ $activeColor }}-500 to-{{ $isPullActive ? 'yellow' : 'cyan' }}-500 rounded-full transition-all duration-500"
                             style="width: {{ $serverImportProgress }}%"></div>
                    </div>
                    
                    {{-- Status Message --}}
                    <p class="text-xs text-gray-400 mb-3">{{ $serverImportMessage ?: ($isPullActive ? 'Importing' : 'Exporting') . ' data...' }}</p>
                    
                    {{-- Live Log Window --}}
                    <div class="mt-4 p-3 bg-black/40 rounded-xl border border-white/10">
                        <p class="text-[10px] text-gray-500 uppercase tracking-wider mb-2 flex items-center gap-2">
                            <i class="fas fa-terminal text-{{ $activeColor }}-500"></i>
                            Live Transfer Log
                        </p>
                        <div class="h-32 overflow-y-auto font-mono text-xs space-y-1">
                            @php
                                $logLines = $serverImportSyncLog ? array_filter(explode("\n", $serverImportSyncLog)) : [];
                                $logLines = array_slice($logLines, -20);
                            @endphp
                            @forelse($logLines as $line)
                                <p class="text-green-400 truncate">{{ $line }}</p>
                            @empty
                                <p class="text-gray-500 italic">{{ $activeLabel }} starting... Preparing SSH keys and connection.</p>
                            @endforelse
                        </div>
                    </div>
                    
                    {{-- Stats --}}
                    @if($activeServerImport && ($activeServerImport['files_transferred'] ?? 0) > 0)
                    <div class="mt-3 flex items-center justify-between text-xs">
                        <span class="text-gray-500">
                            <i class="fas fa-file mr-1"></i>
                            {{ $activeServerImport['files_transferred'] }} / {{ $activeServerImport['file_count'] ?? '?' }} files
                        </span>
                        <span class="text-green-400">
                            {{ $activeServerImport['transferred_size'] ?? '0 MB' }}
                        </span>
                    </div>
                    @endif
                    
                    {{-- Cancel Button --}}
                    <div class="mt-4 flex items-center gap-3">
                        <button wire:click="cancelServerImport"
                                wire:loading.attr="disabled"
                                class="px-4 py-2 rounded-xl bg-red-500/20 text-red-400 border border-red-500/30 text-xs font-bold uppercase tracking-wider hover:bg-red-500 hover:text-white transition-all">
                            <i class="fas fa-times mr-1"></i> Cancel
                        </button>
                    </div>
                </div>
                @endif

                {{-- Sync Completed --}}
                @if($activeServerImport && ($activeServerImport['status'] ?? '') === 'completed')
                @php
                    $isPullCompleted = ($activeServerImport['sync_direction'] ?? 'pull') === 'pull';
                    $completedLabel = $isPullCompleted ? 'Import' : 'Export';
                @endphp
                <div class="p-4 bg-green-500/10 border border-green-500/30 rounded-2xl">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-check-circle text-green-500 text-2xl"></i>
                        <div>
                            <p class="text-sm font-bold text-white">{{ $completedLabel }} Completed!</p>
                            <p class="text-xs text-gray-400">
                                Successfully {{ $isPullCompleted ? 'imported from' : 'exported to' }} {{ $activeServerImport['source_ip'] }}
                                @if($activeServerImport['duration_seconds'])
                                    in {{ floor($activeServerImport['duration_seconds'] / 60) }} min {{ $activeServerImport['duration_seconds'] % 60 }} sec
                                @endif
                            </p>
                            
                            @if($activeServerImport['has_aapanel'] ?? false)
                            <p class="text-xs text-green-400 mt-1">
                                <i class="fas fa-check mr-1"></i> aaPanel detected and migrated
                            </p>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                {{-- Sync Form --}}
                @if(!$activeServerImport || in_array($activeServerImport['status'] ?? '', ['completed', 'failed']))
                <div x-show="showSyncForm" x-transition>
                    {{-- Remote Server Details --}}
                    <div class="p-4 bg-white/5 rounded-2xl space-y-4">
                        <p class="text-xs text-gray-400 uppercase tracking-widest font-bold">
                            <span x-show="syncDirection === 'pull'">Source Server (Import From)</span>
                            <span x-show="syncDirection === 'push'" x-cloak>Target Server (Export To)</span>
                        </p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs text-gray-400 mb-2">IP Address</label>
                                <input type="text" 
                                       x-model="sourceIp"
                                       placeholder="192.168.1.100"
                                       class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white text-sm placeholder-gray-600 focus:border-orange-500 focus:outline-none transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-400 mb-2">Root Password</label>
                                <input type="password" 
                                       x-model="sourcePassword"
                                       placeholder="Enter root password"
                                       class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white text-sm placeholder-gray-600 focus:border-orange-500 focus:outline-none transition-colors">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-400 mb-2">SSH Port (Default: 22)</label>
                                <input type="number" 
                                       x-model="sourcePort"
                                       min="1"
                                       max="65535"
                                       class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white text-sm placeholder-gray-600 focus:border-orange-500 focus:outline-none transition-colors">
                            </div>
                        </div>

                        {{-- Connection Test --}}
                        <div class="flex items-center justify-between pt-2">
                            <button @click="testConnection"
                                    :disabled="testingConnection || !sourceIp || !sourcePassword"
                                    class="px-4 py-2 rounded-xl bg-white/10 text-gray-300 border border-white/20 text-xs font-bold uppercase tracking-wider hover:bg-orange-500/20 hover:border-orange-500/50 hover:text-orange-400 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                <span x-show="!testingConnection">
                                    <i class="fas fa-plug mr-1"></i> Test Connection
                                </span>
                                <span x-show="testingConnection">
                                    <i class="fas fa-circle-notch fa-spin mr-1"></i> Testing...
                                </span>
                            </button>
                            
                            <button wire:click="startServerImport"
                                    wire:loading.attr="disabled"
                                    :disabled="!connectionTested || !connectionResult?.success"
                                    class="px-6 py-2 rounded-xl bg-gradient-to-r {{ $serverImportSyncDirection === 'pull' ? 'from-orange-500 to-yellow-500 hover:shadow-orange-500/30' : 'from-blue-500 to-cyan-500 hover:shadow-blue-500/30' }} text-dark font-black text-xs uppercase tracking-wider hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                <span wire:loading.remove wire:target="startServerImport">
                                    <i class="fas fa-rocket mr-1"></i> 
                                    <span>{{ $serverImportSyncDirection === 'pull' ? 'Start Import' : 'Start Export' }}</span>
                                </span>
                                <span wire:loading wire:target="startServerImport">
                                    <i class="fas fa-circle-notch fa-spin mr-1"></i> 
                                    <span>{{ $serverImportSyncDirection === 'pull' ? 'Starting Import...' : 'Starting Export...' }}</span>
                                </span>
                            </button>
                        </div>

                        {{-- Connection Test Result --}}
                        <div x-show="connectionTested && connectionResult" 
                             x-transition
                             :class="connectionResult?.success 
                                ? (connectionResult?.details?.warning === 'sshpass_not_installed' 
                                    ? 'bg-yellow-500/10 border-yellow-500/30' 
                                    : 'bg-green-500/10 border-green-500/30')
                                : 'bg-red-500/10 border-red-500/30'"
                             class="p-3 rounded-xl border">
                            <p :class="connectionResult?.success 
                                ? (connectionResult?.details?.warning === 'sshpass_not_installed' 
                                    ? 'text-yellow-400' 
                                    : 'text-green-400')
                                : 'text-red-400'" 
                               class="text-xs font-bold">
                                <i :class="connectionResult?.success 
                                    ? (connectionResult?.details?.warning === 'sshpass_not_installed' 
                                        ? 'fas fa-exclamation-triangle' 
                                        : 'fas fa-check-circle')
                                    : 'fas fa-exclamation-circle'" class="mr-1"></i>
                                <span x-text="connectionResult?.message"></span>
                            </p>
                            <template x-if="connectionResult?.details?.warning === 'sshpass_not_installed'">
                                <p class="mt-2 text-xs text-orange-400">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    SSH tools will be auto-installed when you start the import.
                                </p>
                            </template>
                            <template x-if="connectionResult?.details && !connectionResult?.details?.warning">
                                <div class="mt-2 text-xs text-gray-400 space-y-1">
                                    <p x-text="'OS: ' + (connectionResult.details.os || 'Unknown')"></p>
                                    <p x-text="'Disk: ' + (connectionResult.details.disk_total || 'Unknown')"></p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                @endif

                {{-- How It Works --}}
                <div class="p-4 bg-orange-500/5 border border-orange-500/20 rounded-2xl">
                    <p class="text-xs text-orange-400 font-bold mb-3"><i class="fas fa-info-circle mr-1"></i>How It Works</p>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        {{-- Step 1: Key Setup --}}
                        <div class="text-center p-3 bg-white/5 rounded-xl">
                            <div class="w-8 h-8 rounded-full bg-orange-500/20 text-orange-500 flex items-center justify-center mx-auto mb-2 text-xs font-bold">1</div>
                            <p class="text-xs text-white font-bold">SSH Key Setup</p>
                            <p class="text-[10px] text-gray-500">Auto-generate & deploy keys</p>
                        </div>
                        {{-- Step 2: Connect --}}
                        <div class="text-center p-3 bg-white/5 rounded-xl">
                            <div class="w-8 h-8 rounded-full bg-orange-500/20 text-orange-500 flex items-center justify-center mx-auto mb-2 text-xs font-bold">2</div>
                            <p class="text-xs text-white font-bold">Connect</p>
                            <p class="text-[10px] text-gray-500">
                                <span x-show="syncDirection === 'pull'">SSH to source server</span>
                                <span x-show="syncDirection === 'push'" x-cloak>SSH to target server</span>
                            </p>
                        </div>
                        {{-- Step 3: Sync --}}
                        <div class="text-center p-3 bg-white/5 rounded-xl">
                            <div class="w-8 h-8 rounded-full bg-orange-500/20 text-orange-500 flex items-center justify-center mx-auto mb-2 text-xs font-bold">3</div>
                            <p class="text-xs text-white font-bold">Sync</p>
                            <p class="text-[10px] text-gray-500">
                                <span x-show="syncDirection === 'pull'">Pull data to BelieVoo</span>
                                <span x-show="syncDirection === 'push'" x-cloak>Push data to remote</span>
                            </p>
                        </div>
                        {{-- Step 4: Done --}}
                        <div class="text-center p-3 bg-white/5 rounded-xl">
                            <div class="w-8 h-8 rounded-full bg-orange-500/20 text-orange-500 flex items-center justify-center mx-auto mb-2 text-xs font-bold">4</div>
                            <p class="text-xs text-white font-bold">Complete</p>
                            <p class="text-[10px] text-gray-500">
                                <span x-show="syncDirection === 'pull'">Data on BelieVoo VPS</span>
                                <span x-show="syncDirection === 'push'" x-cloak>Data on remote server</span>
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Benefits & Info --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 bg-green-500/5 border border-green-500/20 rounded-2xl">
                        <p class="text-xs text-green-400 font-bold mb-2"><i class="fas fa-check-circle mr-1"></i>What's Included</p>
                        <ul class="text-xs text-gray-400 space-y-1">
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check text-green-500 mt-0.5 text-[10px]"></i>
                                <span>All websites and web files</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check text-green-500 mt-0.5 text-[10px]"></i>
                                <span>Databases (MySQL/PostgreSQL)</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check text-green-500 mt-0.5 text-[10px]"></i>
                                <span>aaPanel (if installed)</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check text-green-500 mt-0.5 text-[10px]"></i>
                                <span>User accounts and configs</span>
                            </li>
                        </ul>
                    </div>
                    <div class="p-4 bg-yellow-500/5 border border-yellow-500/20 rounded-2xl">
                        <p class="text-xs text-yellow-400 font-bold mb-2"><i class="fas fa-exclamation-triangle mr-1"></i>Important Notes</p>
                        <ul class="text-xs text-gray-400 space-y-1">
                            <li class="flex items-start gap-2">
                                <i class="fas fa-times text-red-500 mt-0.5 text-[10px]"></i>
                                <span>System files excluded (/dev, /proc, /sys)</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-times text-red-500 mt-0.5 text-[10px]"></i>
                                <span>Old server network config reset</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-info text-yellow-500 mt-0.5 text-[10px]"></i>
                                <span>Time depends on data size</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-info text-yellow-500 mt-0.5 text-[10px]"></i>
                                <span>Source server stays online</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

            </div>
        </div>
    </div>
    @endif
@else
    {{-- Hosting List View --}}
    @if($hostings->count() > 0)
        <div class="space-y-6">
            <div class="flex justify-between items-center">
                <h2 class="text-2xl font-black text-white uppercase tracking-tight">Your Hosting Plans</h2>
                <a href="{{ route('services.index') }}" class="px-6 py-3 rounded-xl bg-electric-blue/10 text-electric-blue font-black uppercase tracking-widest text-[10px] hover:bg-electric-blue hover:text-dark transition-all">
                    <i class="fas fa-plus mr-2"></i>Buy New Hosting
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($hostings as $hosting)
                    @if(in_array($hosting->status, ['pending', 'provisioning']))
                    {{-- PENDING/PROVISIONING - "Setting up your server" Card --}}
                    @php
                        $isProvisioning = $hosting->status === 'provisioning';
                        $createdAt = $hosting->created_at;
                        $minutesElapsed = $createdAt ? now()->diffInMinutes($createdAt) : 0;
                        $estimatedMinutes = 15;
                        $progressPct = min(95, ($minutesElapsed / $estimatedMinutes) * 100);
                    @endphp
                    <div class="p-6 glass rounded-3xl border {{ $isProvisioning ? 'border-electric-blue/30 bg-electric-blue/5' : 'border-yellow-500/30 bg-yellow-500/5' }} relative overflow-hidden">
                        <div class="absolute inset-0 opacity-5" style="background: radial-gradient(circle at 50% 50%, {{ $isProvisioning ? '#00b7ff' : '#f59e0b' }}, transparent 70%); animation: pulse 3s ease-in-out infinite;"></div>

                        <div class="relative z-10">
                            <div class="flex items-start justify-between mb-5">
                                <div class="w-14 h-14 rounded-2xl {{ $isProvisioning ? 'bg-electric-blue/20' : 'bg-yellow-500/20' }} flex items-center justify-center text-2xl {{ $isProvisioning ? 'text-electric-blue' : 'text-yellow-400' }}">
                                    <i class="fas {{ $isProvisioning ? 'fa-server' : 'fa-clock' }} {{ $isProvisioning ? 'animate-pulse' : '' }}"></i>
                                </div>
                                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest {{ $isProvisioning ? 'bg-electric-blue/20 text-electric-blue border border-electric-blue/30' : 'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30' }}">
                                    <i class="fas fa-circle text-[6px] mr-1 animate-pulse"></i>
                                    {{ $isProvisioning ? 'Setting Up' : 'Processing' }}
                                </span>
                            </div>

                            <h4 class="font-black text-white uppercase tracking-tight mb-1">{{ $hosting->plan_name }}</h4>
                            <p class="text-xs text-gray-400 mb-1">{{ ucfirst($hosting->hosting_type) }} • {{ $hosting->cpu_cores ?? '?' }} vCPU • {{ $hosting->ram_size ?? '?' }} RAM</p>

                            @if($isProvisioning)
                            {{-- Provisioning Progress --}}
                            <p class="text-xs text-electric-blue font-bold mb-3">
                                <i class="fas fa-clock mr-1"></i>Setting up your server (~{{ $estimatedMinutes }} min)
                            </p>
                            <div class="h-2 bg-white/10 rounded-full overflow-hidden mb-4">
                                <div class="h-full bg-gradient-to-r from-electric-blue to-electric-violet rounded-full transition-all duration-1000"
                                     style="width: {{ max(5, $progressPct) }}%"></div>
                            </div>
                            @endif

                            {{-- Progress Steps --}}
                            <div class="space-y-3 mb-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-6 h-6 rounded-full bg-green-500/20 border border-green-500/40 flex items-center justify-center flex-shrink-0">
                                        <i class="fas fa-check text-green-400 text-[10px]"></i>
                                    </div>
                                    <span class="text-xs font-bold text-green-400">Payment Confirmed</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="w-6 h-6 rounded-full {{ $isProvisioning ? 'bg-electric-blue/20 border-electric-blue/40' : 'bg-yellow-500/20 border-yellow-500/40' }} border flex items-center justify-center flex-shrink-0 animate-pulse">
                                        <i class="fas fa-cog {{ $isProvisioning ? 'text-electric-blue' : 'text-yellow-400' }} text-[10px]"></i>
                                    </div>
                                    <span class="text-xs font-bold {{ $isProvisioning ? 'text-electric-blue' : 'text-yellow-400' }}">
                                        {{ $isProvisioning ? 'Creating VM on Proxmox...' : 'Server Setup in Progress' }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="w-6 h-6 rounded-full bg-white/5 border border-white/10 flex items-center justify-center flex-shrink-0">
                                        <i class="fas fa-server text-gray-600 text-[10px]"></i>
                                    </div>
                                    <span class="text-xs font-bold text-gray-500">Server Details Ready</span>
                                </div>
                            </div>

                            <div class="p-3 rounded-xl {{ $isProvisioning ? 'bg-electric-blue/10 border-electric-blue/20' : 'bg-yellow-500/10 border-yellow-500/20' }} border">
                                <p class="text-[10px] font-bold {{ $isProvisioning ? 'text-electric-blue' : 'text-yellow-400' }} text-center">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    {{ $isProvisioning
                                        ? 'Your VM is being created. This takes ~15 minutes. Page auto-refreshes.'
                                        : 'Our team is setting up your server. You\'ll be notified once it\'s ready.' }}
                                </p>
                            </div>

                            @if($hosting->order)
                                <div class="mt-3 text-center">
                                    <span class="text-[10px] text-gray-600 font-mono">{{ $hosting->order->order_number }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                    @else
                    {{-- ACTIVE / OTHER STATUS Card --}}
                    <a href="{{ route('client.dashboard', ['tab' => 'hosting', 'hosting' => $hosting->id]) }}"
                       class="block p-6 glass rounded-3xl border border-white/10 group hover:border-electric-blue/30 transition-all cursor-pointer no-underline">
                        <div class="flex items-start justify-between mb-6">
                            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-electric-blue/20 to-electric-violet/20 flex items-center justify-center text-2xl text-electric-blue">
                                @if($hosting->hosting_type == 'vps')
                                    <i class="fas fa-server"></i>
                                @elseif($hosting->hosting_type == 'dedicated')
                                    <i class="fas fa-database"></i>
                                @else
                                    <i class="fas fa-cloud"></i>
                                @endif
                            </div>
                            <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest {{ $hosting->getStatusBadgeClass() }}">
                                {{ $hosting->status }}
                            </span>
                        </div>

                        <h4 class="font-black text-white uppercase tracking-tight mb-1">{{ $hosting->plan_name }}</h4>
                        <p class="text-xs text-gray-400 mb-4">{{ ucfirst($hosting->hosting_type) }} Hosting</p>

                        <div class="space-y-3 mb-6">
                            @if($hosting->primary_domain)
                                <div class="flex items-center text-xs text-gray-400">
                                    <i class="fas fa-globe w-5 text-electric-blue"></i>
                                    <span class="truncate">{{ $hosting->primary_domain }}</span>
                                </div>
                            @endif
                            @if($hosting->server_ip)
                                <div class="flex items-center text-xs text-gray-400">
                                    <i class="fas fa-network-wired w-5 text-electric-blue"></i>
                                    <span>{{ $hosting->server_ip }}</span>
                                </div>
                            @endif
                        </div>

                        <div class="flex items-center justify-between pt-4 border-t border-white/5">
                            <div>
                                <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Expires</p>
                                <p class="text-sm font-bold {{ $hosting->isExpiringSoon() ? 'text-red-500' : 'text-white' }}">
                                    {{ $hosting->expiry_date->format('M d, Y') }}
                                    @if($hosting->isExpiringSoon())
                                        <span class="text-xs">({{ $hosting->getDaysRemaining() }}d)</span>
                                    @endif
                                </p>
                            </div>
                            <div class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center group-hover:bg-electric-blue group-hover:text-dark transition-all">
                                <i class="fas fa-chevron-right text-sm"></i>
                            </div>
                        </div>
                    </a>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- Upgrade Options --}}
        @if($availableHostings->count() > 0)
            <div class="mt-12 pt-12 border-t border-white/10">
                <h3 class="text-xl font-black text-white uppercase tracking-tight mb-6">Available Upgrades</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($availableHostings as $service)
                        <div class="p-6 glass rounded-3xl border border-white/10 hover:border-electric-blue/30 transition-all">
                            <div class="flex items-center space-x-3 mb-4">
                                <div class="w-12 h-12 rounded-xl bg-electric-blue/10 flex items-center justify-center text-electric-blue">
                                    <i class="fas fa-arrow-up"></i>
                                </div>
                                <div>
                                    <h4 class="font-black text-white uppercase tracking-tight">{{ $service->title }}</h4>
                                    <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">{{ $service->category }}</p>
                                </div>
                            </div>

                            @if(!empty($service->pricing_tiers))
                                <div class="space-y-2 mb-6">
                                    @foreach($service->pricing_tiers as $tier)
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-gray-400">{{ $tier['name'] }}</span>
                                            <span class="font-bold text-white">{{ $currencySymbol }}{{ $tier['price'] }}/{{ $tier['billing_cycle'] ?? 'mo' }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-2xl font-black text-electric-blue mb-6">{{ $currencySymbol }}{{ $service->price }}<span class="text-sm text-gray-500">/{{ $service->price_label ?? 'mo' }}</span></p>
                            @endif

                            <a href="{{ route('checkout', ['service' => $service->slug]) }}" class="w-full py-3 rounded-xl bg-electric-blue text-dark font-black uppercase tracking-widest text-xs hover:scale-105 transition-all text-center block">
                                Upgrade Now
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @else
        {{-- No Hosting - GHC Promo --}}
        <div class="text-center py-16 glass rounded-[3rem]">
            <div class="w-20 h-20 rounded-full bg-gradient-to-br from-electric-blue/20 to-electric-violet/20 flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-cloud text-4xl text-electric-blue"></i>
            </div>
            <h3 class="text-3xl font-black text-white uppercase mb-4">Go Host Cloud (GHC)</h3>
            <p class="text-gray-400 mb-8 max-w-2xl mx-auto px-6">
                Believoo ka hosting aur domain services ab GHC pe available hain. VPS, Web Hosting, Domains aur Dedicated Servers kharidne ke liye GHC par jayein.
            </p>
            <a href="https://ghc.believoo.com" target="_blank" rel="noopener" class="inline-flex items-center px-8 py-4 rounded-2xl bg-electric-blue text-dark font-black uppercase tracking-widest text-xs hover:scale-105 transition-all">
                <i class="fas fa-external-link-alt mr-2"></i> Go Hosting
            </a>
        </div>
    @endif

    {{-- Action Confirmation Modal --}}
    @if($showActionModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm" x-data x-init="$el.addEventListener('click', (e) => { if(e.target === $el) $wire.cancelServerAction() })">
            <div class="w-full max-w-md p-6 glass rounded-3xl border border-white/20 shadow-2xl">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 rounded-full bg-yellow-500/20 flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-yellow-500 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-white uppercase tracking-wider">{{ $pendingActionTitle }}</h3>
                        <p class="text-sm text-gray-400">Server Action Required</p>
                    </div>
                </div>
                <p class="text-sm text-gray-300 mb-6 leading-relaxed">{{ $pendingActionMessage }}</p>
                <div class="flex gap-3 justify-end">
                    <button wire:click="cancelServerAction" class="px-5 py-2.5 rounded-xl bg-white/5 text-gray-400 font-black uppercase tracking-wider text-xs hover:bg-white/10 hover:text-white transition-all">
                        Cancel
                    </button>
                    <button wire:click="executeServerAction" wire:loading.attr="disabled" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-red-500 to-red-600 text-white font-black uppercase tracking-wider text-xs hover:from-red-600 hover:to-red-700 transition-all flex items-center gap-2">
                        <span wire:loading.remove wire:target="executeServerAction">
                            <i class="fas fa-check mr-1"></i> Confirm
                        </span>
                        <span wire:loading wire:target="executeServerAction">
                            <i class="fas fa-spinner fa-spin mr-1"></i> Processing...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    @once
    <script>
        // BelieVoo Nameservers - used by domain connection modal
        const believooNs = ['ns1.believoo.com', 'ns2.believoo.com'];

        document.addEventListener('livewire:initialized', () => {
            Livewire.on('clearServerMessage', () => {
                // Auto-clear message handled by Alpine x-init
            });
        });

        // DNS Records Management - Hostinger/OVH Style
        window.fetchDnsRecords = async function(domainId) {
            if (!domainId) {
                alert('No domain selected');
                return;
            }

            try {
                const button = document.querySelector('a[onclick*="fetchDnsRecords"]');
                if (button) {
                    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Loading...';
                    button.classList.add('opacity-50');
                }

                const response = await fetch(`/api/dns/records/${domainId}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    credentials: 'same-origin'
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();

                if (data.success) {
                    // Refresh the page to show updated records
                    // Or use Livewire to refresh the component
                    if (typeof Livewire !== 'undefined') {
                        Livewire.dispatch('refresh');
                    } else {
                        location.reload();
                    }
                } else {
                    alert('Failed to fetch DNS records: ' + (data.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error fetching DNS records:', error);
                alert('Error fetching DNS records: ' + error.message);
            } finally {
                const button = document.querySelector('a[onclick*="fetchDnsRecords"]');
                if (button) {
                    button.innerHTML = '<i class="fas fa-sync mr-2"></i>Fetch from Provider';
                    button.classList.remove('opacity-50');
                }
            }
        }

        // Nameserver check function
        window.checkNameserverStatus = async function(domainId) {
            if (!domainId) return;

            try {
                const response = await fetch(`/api/dns/nameservers/${domainId}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    credentials: 'same-origin'
                });

                const data = await response.json();

                if (data.success && data.ns_status?.needs_update) {
                    // Show alert if nameservers need update
                    const alertDiv = document.getElementById('nameserver-alert');
                    if (alertDiv) {
                        alertDiv.classList.remove('hidden');
                    }
                }
            } catch (error) {
                console.error('Error checking nameserver status:', error);
            }
        }

        // Submit External Domain Connection
        window.submitExternalDomain = function() {
            const domainName = document.getElementById('external-domain-name').value.trim();
            const currentNs = document.getElementById('external-domain-ns').value.trim();

            if (!domainName) {
                alert('Please enter a domain name');
                return;
            }

            // Validate domain format
            const domainRegex = /^[a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.[a-zA-Z]{2,}$/;
            if (!domainRegex.test(domainName)) {
                alert('Invalid domain name format. Please enter a valid domain like example.com');
                return;
            }

            // Show loading state
            const button = document.querySelector('button[onclick="submitExternalDomain()"]');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Connecting...';
            button.disabled = true;

            // Submit to API
            fetch('/api/domains/connect-external', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    domain_name: domainName.toLowerCase(),
                    current_nameservers: currentNs ? currentNs.split(',').map(ns => ns.trim()) : []
                }),
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                button.innerHTML = originalText;
                button.disabled = false;

                if (data.success) {
                    // Hide modal
                    document.getElementById('connect-domain-modal').classList.add('hidden');
                    // Show success message
                    alert(`Domain connected successfully!\n\nNext steps:\n1. Go to your domain registrar (${data.registrar || 'where you bought the domain'})\n2. Update nameservers to:\n   ns1.believoo.com\n   ns2.believoo.com\n3. DNS management will be active within 24-48 hours`);
                    // Refresh page
                    location.reload();
                } else {
                    alert('Failed to connect domain: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                button.innerHTML = originalText;
                button.disabled = false;
                console.error('Error connecting domain:', error);
                alert('Error connecting domain: ' + error.message);
            });
        }

        // Open Add DNS Record Modal
        window.openAddDnsRecordModal = function(domainId) {
            // Show a simple prompt for now - can be enhanced with a proper modal
            const recordType = prompt('Select Record Type:\nA, AAAA, CNAME, MX, TXT, NS, SRV, CAA');
            if (!recordType) return;

            const name = prompt('Enter Name (e.g., @, www, mail):');
            if (!name) return;

            const value = prompt('Enter Value:');
            if (!value) return;

            const ttl = prompt('Enter TTL (default 3600):', '3600') || 3600;

            const priority = ['MX', 'SRV'].includes(recordType.toUpperCase())
                ? prompt('Enter Priority (default 10):', '10') || 10
                : null;

            // Submit the record
            fetch('/api/dns/records', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    domain_id: domainId,
                    record_type: recordType.toUpperCase(),
                    name: name,
                    value: value,
                    ttl: parseInt(ttl),
                    priority: priority ? parseInt(priority) : null
                }),
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('DNS Record created successfully!');
                    // Refresh to show the new record
                    if (typeof Livewire !== 'undefined') {
                        Livewire.dispatch('refresh');
                    } else {
                        location.reload();
                    }
                } else {
                    alert('Failed to create DNS record: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error creating DNS record:', error);
                alert('Error creating DNS record: ' + error.message);
            });
        }

        // BelieVoo WebSocket Manager with Exponential Backoff
        const BelieVooWebSocket = {
            connections: new Map(),
            maxReconnectAttempts: 5,
            baseReconnectDelay: 1000,
            maxReconnectDelay: 30000,

            create(url, options = {}) {
                const ws = new WebSocket(url);
                const connId = Math.random().toString(36).substr(2, 9);
                
                const conn = {
                    ws: ws,
                    url: url,
                    options: options,
                    reconnectAttempts: 0,
                    reconnectTimer: null,
                    isIntentionallyClosed: false,
                    onOpen: options.onOpen || (() => {}),
                    onMessage: options.onMessage || (() => {}),
                    onClose: options.onClose || (() => {}),
                    onError: options.onError || (() => {}),
                };

                ws.onopen = (event) => {
                    conn.reconnectAttempts = 0;
                    console.log(`[WebSocket ${connId}] Connected`);
                    conn.onOpen(event);
                };

                ws.onmessage = (event) => {
                    conn.onMessage(event);
                };

                ws.onclose = (event) => {
                    if (!conn.isIntentionallyClosed && conn.reconnectAttempts < this.maxReconnectAttempts) {
                        const delay = Math.min(
                            this.baseReconnectDelay * Math.pow(2, conn.reconnectAttempts),
                            this.maxReconnectDelay
                        );
                        conn.reconnectAttempts++;
                        
                        console.log(`[WebSocket ${connId}] Reconnecting in ${delay}ms (attempt ${conn.reconnectAttempts})`);
                        
                        conn.reconnectTimer = setTimeout(() => {
                            const newConn = this.create(url, options);
                            this.connections.set(connId, newConn);
                        }, delay);
                    } else if (conn.reconnectAttempts >= this.maxReconnectAttempts) {
                        console.error(`[WebSocket ${connId}] Max reconnect attempts reached`);
                    }
                    
                    conn.onClose(event);
                };

                ws.onerror = (error) => {
                    console.error(`[WebSocket ${connId}] Error:`, error);
                    conn.onError(error);
                };

                this.connections.set(connId, conn);
                
                return {
                    id: connId,
                    send: (data) => ws.send(data),
                    close: () => {
                        conn.isIntentionallyClosed = true;
                        if (conn.reconnectTimer) {
                            clearTimeout(conn.reconnectTimer);
                        }
                        ws.close();
                        this.connections.delete(connId);
                    },
                    getState: () => ws.readyState,
                };
            },

            closeAll() {
                this.connections.forEach((conn, id) => {
                    conn.isIntentionallyClosed = true;
                    if (conn.reconnectTimer) {
                        clearTimeout(conn.reconnectTimer);
                    }
                    conn.ws.close();
                });
                this.connections.clear();
            },
        };

        // Expose to global scope for use in other scripts
        window.BelieVooWebSocket = BelieVooWebSocket;

        // Handle page visibility changes for WebSocket connections
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                // Refresh any stale connections when page becomes visible
                BelieVooWebSocket.connections.forEach((conn, id) => {
                    if (conn.ws.readyState === WebSocket.CLOSED && !conn.isIntentionallyClosed) {
                        console.log(`[WebSocket ${id}] Page visible, reconnecting...`);
                        conn.reconnectAttempts = 0;
                        const newConn = BelieVooWebSocket.create(conn.url, conn.options);
                        BelieVooWebSocket.connections.set(id, newConn);
                    }
                });
            }
        });
    </script>
    @endonce

    {{-- Nameserver Modal --}}
    <div @class(['fixed inset-0 z-[9999] items-center justify-center bg-black/70 backdrop-blur-sm', 'hidden' => !$showNsModal])>
        <div class="bg-gray-900 border border-white/10 rounded-2xl shadow-2xl p-6 max-w-lg w-full mx-4">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-xl font-black text-white uppercase tracking-tight">Change Nameservers</h3>
                    <p class="text-xs text-gray-400 mt-1">Update your domain's nameservers</p>
                </div>
                <button type="button" wire:click="closeNsModal" class="text-gray-400 hover:text-white"><i class="fas fa-times text-xl"></i></button>
            </div>
            @if($nsError)
            <div class="mb-4 p-3 bg-red-500/10 border border-red-500/20 rounded-xl">
                <p class="text-sm text-red-400">{{ $nsError }}</p>
            </div>
            @endif
            <div class="space-y-4 mb-6">
                <input type="text" wire:model="ns1" placeholder="ns1.believoo.com" class="w-full px-3 py-2 rounded-xl bg-white/10 border border-white/20 text-sm text-white">
                <input type="text" wire:model="ns2" placeholder="ns2.believoo.com" class="w-full px-3 py-2 rounded-xl bg-white/10 border border-white/20 text-sm text-white">
                <input type="text" wire:model="ns3" placeholder="ns3.believoo.com (Optional)" class="w-full px-3 py-2 rounded-xl bg-white/10 border border-white/20 text-sm text-white">
                <input type="text" wire:model="ns4" placeholder="ns4.believoo.com (Optional)" class="w-full px-3 py-2 rounded-xl bg-white/10 border border-white/20 text-sm text-white">
            </div>
            <div class="flex justify-between">
                <button type="button" wire:click="resetToBelievooNs" class="text-xs text-electric-blue underline">Reset to BelieVoo NS</button>
                <div class="flex gap-3">
                    <button type="button" wire:click="closeNsModal" class="px-4 py-2 rounded-xl bg-white/5 text-gray-400 text-xs font-bold">Cancel</button>
                    <button type="button" wire:click="saveNameservers" wire:loading.attr="disabled" class="px-4 py-2 rounded-xl bg-electric-blue text-white text-xs font-bold">
                        <span wire:loading.remove wire:target="saveNameservers">Save</span>
                        <span wire:loading wire:target="saveNameservers"><i class="fas fa-spinner fa-spin"></i></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
{{-- End DNS Manager Tab --}}
</div>
@endif
