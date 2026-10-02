@extends('bconnect.layout')
@section('title', 'Remote Desktop Agent')
@section('content')
<div class="max-w-5xl mx-auto">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-white mb-2">Remote Desktop Access</h2>
        <p class="text-slate-400">AnyDesk-style access: share a session code and connect instantly — or use full OS control via the desktop agent.</p>
    </div>

    <div class="grid lg:grid-cols-3 gap-6 mb-8">
        <div class="bc-card p-6 lg:col-span-2">
            <div class="flex items-start gap-4 mb-6">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl flex-shrink-0" style="background:rgba({{ $bconnectBrand['brand_rgb'] }},0.12);color:var(--bc-cyan);">
                    <i class="fas fa-plug"></i>
                </div>
                <div>
                    <h3 class="font-bold text-white text-lg mb-1">Connect with a Code</h3>
                    <p class="text-slate-400 text-sm mb-4">Enter the code shown on the host device — works with the desktop agent and browser hosts.</p>
                    <a href="{{ route('bconnect.remote.connect') }}" class="bc-btn bc-btn-primary text-sm"><i class="fas fa-keyboard mr-1"></i>Enter Code & Connect</a>
                </div>
            </div>
            <div class="border-t border-slate-800 pt-6">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-purple-500/10 flex items-center justify-center text-purple-400 text-xl flex-shrink-0">
                        <i class="fas fa-keyboard"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-white text-lg mb-1">Full Desktop Agent</h3>
                        <p class="text-slate-400 text-sm mb-3">The BMyDesk Agent gives full mouse & keyboard control. It shows a code on the host machine — that's it.</p>
                        <ul class="text-xs text-slate-500 space-y-1.5 mb-4">
                            <li><i class="fas fa-check text-green-400 mr-1"></i>Screen stream + input control over P2P WebRTC (low latency)</li>
                            <li><i class="fas fa-check text-green-400 mr-1"></i>Host approves every connection — no silent access</li>
                            <li><i class="fas fa-check text-green-400 mr-1"></i>Codes expire after 60 minutes</li>
                        </ul>
                        <div class="flex flex-wrap gap-3">
                            <a href="/downloads/BMyDesk-Agent-Setup-1.0.6.exe" download class="bc-btn bc-btn-primary text-sm"><i class="fab fa-windows mr-1"></i>Windows (Beta)</a>
                            <a href="/downloads/BMyDesk-Agent-v1.0.6.apk" download class="bc-btn bc-btn-primary text-sm"><i class="fab fa-android mr-1"></i>Android APK (Beta)</a>
                            <a href="/downloads/BMyDesk-Agent-1.0.6-win.zip" download class="bc-btn bc-btn-secondary text-sm"><i class="fas fa-file-archive mr-1"></i>Portable ZIP</a>
                            <button class="bc-btn bc-btn-secondary text-sm opacity-60 cursor-not-allowed" title="Coming soon"><i class="fab fa-apple mr-1"></i>iOS (Soon)</button>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-2">Exe download slow ya ruk jaye? <a href="/downloads/BMyDesk-Agent-Setup-1.0.6.zip" download class="text-cyan-400 hover:underline">Zipped installer</a> ya <a href="/downloads/BMyDesk-Agent-1.0.6-win.zip" download class="text-cyan-400 hover:underline">portable ZIP</a> try karo — Chrome mein resume bhi hota hai.</p>
                        <p class="text-[11px] text-slate-600 mt-3">v1.0.6 Beta — Windows installer unsigned (SmartScreen → "More info → Run anyway"); Android is a debug-signed APK (allow "Install unknown apps"). Android host is view-only; control-from-mobile works via the Windows agent.</p>
                        <p class="text-[11px] mt-2"><a href="{{ route('bconnect.remote.applogin') }}" class="text-cyan-400 hover:underline"><i class="fas fa-sign-in-alt mr-1"></i>Already installed? Sign in to the app with this account</a></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bc-card p-6">
            <h3 class="font-bold text-white mb-4"><i class="fas fa-bell mr-2 text-amber-400"></i>Request Beta Access</h3>
            <p class="text-slate-400 text-sm mb-4">Get notified when the desktop agent installer is ready for your workspace.</p>
            @if(session('success'))
            <div class="p-3 mb-4 rounded-lg bg-green-500/10 text-green-400 border border-green-500/20 text-sm">
                <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            </div>
            @endif
            <form method="POST" action="{{ route('bconnect.remote.agent.request') }}" class="space-y-4">@csrf
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
            <i class="fas fa-bolt text-amber-400 text-2xl mb-3"></i>
            <h4 class="font-bold text-white mb-1">P2P & Low Latency</h4>
            <p class="text-slate-400 text-sm">Direct WebRTC stream — sub-second delay on normal networks.</p>
        </div>
        <div class="bc-card p-5 text-center">
            <i class="fas fa-lock text-green-400 text-2xl mb-3"></i>
            <h4 class="font-bold text-white mb-1">Secure Sessions</h4>
            <p class="text-slate-400 text-sm">Unique codes, host approval, encrypted media, session expiry.</p>
        </div>
        <div class="bc-card p-5 text-center">
            <i class="fas fa-history text-purple-400 text-2xl mb-3"></i>
            <h4 class="font-bold text-white mb-1">Session Log</h4>
            <p class="text-slate-400 text-sm">Full audit trail of who requested and approved access.</p>
        </div>
    </div>
</div>
@endsection
