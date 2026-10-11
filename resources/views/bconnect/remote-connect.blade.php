@extends('bconnect.layout')
@section('title', 'Connect to Remote Device')
@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bc-card p-8 md:p-10 text-center">
        <div class="w-16 h-16 mx-auto rounded-2xl flex items-center justify-center mb-5" style="background:rgba({{ $bconnectBrand['brand_rgb'] }},0.12);">
            <i class="fas fa-desktop text-3xl" style="color:var(--bc-cyan)"></i>
        </div>
        <h2 class="text-2xl font-black mb-2">Connect to a device</h2>
        <p class="text-slate-400 text-sm mb-8">Enter the session code shown on the host's screen (BMyDesk Agent app or browser host page).</p>

        @if(session('error'))
        <div class="mb-6 p-3 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-sm text-left">
            <i class="fas fa-exclamation-circle mr-1"></i>{{ session('error') }}
        </div>
        @endif

        <form method="POST" action="{{ route('bconnect.remote.join') }}" id="codeForm">
            @csrf
            <input type="text" name="code" id="codeInput" maxlength="9" autocomplete="off" autocapitalize="characters" spellcheck="false" inputmode="text"
                placeholder="ABC123XY"
                class="w-full max-w-sm mx-auto text-center text-2xl sm:text-3xl font-black tracking-[0.3em] bg-[var(--bc-panel)] border border-[var(--bc-border)] rounded-2xl py-4 px-3 sm:px-6 outline-none focus:border-[var(--bc-cyan)] transition uppercase placeholder:text-slate-600 placeholder:text-lg">
            <button type="submit" class="bc-btn bc-btn-primary mt-6 px-10 py-3.5 text-base">
                <i class="fas fa-plug mr-2"></i>Connect
            </button>
        </form>

        <p class="text-xs text-slate-500 mt-6">The host sees a request and must accept before the screen is shared.</p>

        {{-- Share invite — the host sends this link/code to their viewer --}}
        <div class="mt-4 flex items-center justify-center gap-2 text-xs">
            <span class="text-slate-500">Invite a viewer:</span>
            <button type="button" onclick="shareVia('wa')" class="px-3 py-1.5 rounded-lg bg-green-500/15 text-green-400 font-semibold hover:bg-green-500/25"><i class="fab fa-whatsapp mr-1"></i>WhatsApp</button>
            <button type="button" onclick="shareVia('sms')" class="px-3 py-1.5 rounded-lg bg-cyan-500/15 text-cyan-400 font-semibold hover:bg-cyan-500/25"><i class="fas fa-comment-sms mr-1"></i>SMS</button>
            <button type="button" onclick="shareVia('copy')" class="px-3 py-1.5 rounded-lg bg-slate-500/15 text-slate-300 font-semibold hover:bg-slate-500/25"><i class="fas fa-link mr-1"></i>Copy link</button>
        </div>
        <script>
        function shareVia(kind) {
            var code = document.getElementById('codeInput').value.trim().toUpperCase();
            if (code.length < 6) { code = prompt('Enter the host code to share:'); if (!code) return; code = code.toUpperCase(); }
            var url = 'https://bmydesk.believoo.com/remote/guest/' + code;
            var text = 'Connect to my device on BMyDesk — open ' + url;
            if (kind === 'wa') window.open('https://wa.me/?text=' + encodeURIComponent(text), '_blank');
            else if (kind === 'sms') location.href = 'sms:?&body=' + encodeURIComponent(text);
            else { navigator.clipboard.writeText(url).then(() => alert('Link copied: ' + url)); }
        }
        </script>
    </div>

    <div class="grid md:grid-cols-2 gap-4 mt-6">
        <a href="{{ route('bconnect.remote.agent') }}" class="bc-card bc-card-hover p-6 flex items-start gap-4 no-underline">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0" style="background:rgba(245,158,11,0.12);">
                <i class="fas fa-laptop text-xl text-amber-400"></i>
            </div>
            <div>
                <h3 class="font-bold mb-1 text-[var(--bc-text)]">Need full control?</h3>
                <p class="text-xs text-slate-400">Install the BMyDesk Agent on the host device for mouse & keyboard control.</p>
            </div>
        </a>
        <button onclick="startBrowserHost()" class="bc-card bc-card-hover p-6 flex items-start gap-4 text-left w-full border border-[var(--bc-border)]" style="background:var(--bc-card);">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0" style="background:rgba({{ $bconnectBrand['brand_rgb'] }},0.12);">
                <i class="fas fa-share-square text-xl" style="color:var(--bc-cyan)"></i>
            </div>
            <div>
                <h3 class="font-bold mb-1 text-[var(--bc-text)]">Share my screen</h3>
                <p class="text-xs text-slate-400">Get a code and let someone view this browser screen. No install needed.</p>
            </div>
        </button>
    </div>
</div>

<script>
const codeInput = document.getElementById('codeInput');
codeInput.addEventListener('input', () => {
    codeInput.value = codeInput.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
});
codeInput.focus();

async function startBrowserHost() {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    try {
        const r = await fetch('{{ route('bconnect.remote.host.start') }}', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
            body: JSON.stringify({_token: csrf}),
            credentials: 'same-origin',
        });
        const data = await r.json();
        if (data.ok) {
            window.location.href = data.url;
        } else {
            alert('Could not start host session: ' + (data.error || r.status));
        }
    } catch (e) {
        alert('Could not start host session. Remote access may require a Pro/Enterprise plan.');
    }
}
</script>
@endsection
