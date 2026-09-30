@extends('bconnect.layout')
@section('title', 'Remote Desktop Agent')
@section('content')
<div class="max-w-5xl mx-auto">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-white mb-2">Remote Desktop Access</h2>
        <p class="text-slate-400">Browser-based screen sharing works instantly. Full OS control requires the desktop agent.</p>
    </div>

    <div class="grid lg:grid-cols-3 gap-6 mb-8">
        <div class="bc-card p-6 lg:col-span-2">
            <div class="flex items-start gap-4 mb-6">
                <div class="w-12 h-12 rounded-xl bg-cyan-500/10 flex items-center justify-center text-cyan-400 text-xl flex-shrink-0">
                    <i class="fas fa-globe"></i>
                </div>
                <div>
                    <h3 class="font-bold text-white text-lg mb-1">Browser Screen Sharing</h3>
                    <p class="text-slate-400 text-sm mb-4">Start a session from <a href="{{ route('bconnect.remote') }}" class="text-cyan-400 hover:underline">Remote</a> and share your screen without installing anything. Viewers can watch your screen in real-time.</p>
                    <a href="{{ route('bconnect.remote') }}" class="bc-btn bc-btn-primary text-sm"><i class="fas fa-desktop mr-1"></i>Start Browser Session</a>
                </div>
            </div>
            <div class="border-t border-slate-800 pt-6">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-purple-500/10 flex items-center justify-center text-purple-400 text-xl flex-shrink-0">
                        <i class="fas fa-keyboard"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-white text-lg mb-1">Full Desktop Agent</h3>
                        <p class="text-slate-400 text-sm mb-4">Unlock full mouse and keyboard control, multi-monitor support, and background access with the B-CONNECT desktop agent.</p>
                        <div class="flex flex-wrap gap-3">
                            <button class="bc-btn bc-btn-secondary text-sm opacity-60 cursor-not-allowed" title="Coming soon"><i class="fab fa-windows mr-1"></i>Windows Agent (Beta)</button>
                            <button class="bc-btn bc-btn-secondary text-sm opacity-60 cursor-not-allowed" title="Coming soon"><i class="fab fa-apple mr-1"></i>macOS Agent (Beta)</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bc-card p-6">
            <h3 class="font-bold text-white mb-4"><i class="fas fa-bell mr-2 text-amber-400"></i>Request Beta Access</h3>
            <p class="text-slate-400 text-sm mb-4">Get notified when the desktop agent is available for your workspace.</p>
            @if(session('success'))
            <div class="p-3 mb-4 rounded-lg bg-green-500/10 text-green-400 border border-green-500/20 text-sm">
                <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            </div>
            @endif
            <form method="POST" action="{{ route('bconnect.remote.agent.request') }}" class="space-y-4">@csrf
                <input type="hidden" name="os" value="windows">
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Operating System</label>
                    <select name="os" class="bc-input w-full" required>
                        <option value="windows">Windows</option>
                        <option value="macos">macOS</option>
                        <option value="linux">Linux</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Use case (optional)</label>
                    <textarea name="message" rows="3" placeholder="Tell us about your use case" class="bc-input w-full"></textarea>
                </div>
                <button type="submit" class="bc-btn bc-btn-primary w-full"><i class="fas fa-paper-plane mr-1"></i>Request Beta Access</button>
            </form>
        </div>
    </div>

    <div class="grid md:grid-cols-3 gap-4">
        <div class="bc-card p-5 text-center">
            <i class="fas fa-video text-cyan-400 text-2xl mb-3"></i>
            <h4 class="font-bold text-white mb-1">HD Screen Share</h4>
            <p class="text-slate-400 text-sm">Share any window or entire screen in the browser.</p>
        </div>
        <div class="bc-card p-5 text-center">
            <i class="fas fa-lock text-green-400 text-2xl mb-3"></i>
            <h4 class="font-bold text-white mb-1">Secure Sessions</h4>
            <p class="text-slate-400 text-sm">Each session uses a unique code and Agora encryption.</p>
        </div>
        <div class="bc-card p-5 text-center">
            <i class="fas fa-history text-purple-400 text-2xl mb-3"></i>
            <h4 class="font-bold text-white mb-1">Session Log</h4>
            <p class="text-slate-400 text-sm">Full audit trail of who requested and approved access.</p>
        </div>
    </div>
</div>
@endsection
