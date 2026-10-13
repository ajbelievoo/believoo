<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Install BMyDesk Agent — Believoo B-CONNECT</title>
    <meta name="description" content="Step-by-step install guide for the BMyDesk remote agent on Windows and Android.">
    <link rel="icon" href="/img/bmydesk-icon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script>tailwind.config={theme:{extend:{colors:{brand:'#e11d48'}}}}</script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
<nav class="bg-white border-b border-slate-100">
    <div class="max-w-5xl mx-auto px-4 py-4 flex items-center justify-between">
        <a href="/" class="text-xl font-extrabold text-slate-900">BMy<span class="text-brand">Desk</span></a>
        <a href="/" class="text-sm text-slate-500 hover:text-brand"><i class="fas fa-arrow-left mr-1"></i>Back to home</a>
    </div>
</nav>

<div class="max-w-4xl mx-auto px-4 py-14">
    <h1 class="text-4xl font-extrabold text-slate-900 mb-3">Install the BMyDesk Agent</h1>
    <p class="text-slate-600 mb-12">The agent runs on the device you want to access. It shows a permanent code — give that code to whoever should connect.</p>

    {{-- WINDOWS --}}
    <div class="bg-white rounded-2xl border border-slate-100 p-8 mb-8">
        <div class="flex items-center gap-3 mb-6">
            <span class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl"><i class="fab fa-windows"></i></span>
            <h2 class="text-2xl font-bold text-slate-900">Windows</h2>
        </div>
        <a href="/dl/agent.exe" class="inline-block px-6 py-3 bg-brand text-white font-bold rounded-xl mb-8"><i class="fas fa-download mr-2"></i>Download for Windows</a>
        <ol class="space-y-5">
            <li class="flex gap-4"><span class="w-7 h-7 shrink-0 rounded-full bg-brand text-white font-bold text-sm flex items-center justify-center">1</span><div><b>Run the installer</b> — open <code class="bg-slate-100 px-1.5 py-0.5 rounded text-sm">BMyDesk-Agent-Setup-1.3.12.exe</code>. If Windows SmartScreen shows "Windows protected your PC", click <b>More info → Run anyway</b>. This warning appears for every new app until it builds download reputation — it does not mean the file is unsafe (see the verification box below).</div></li>
            <li class="flex gap-4"><span class="w-7 h-7 shrink-0 rounded-full bg-brand text-white font-bold text-sm flex items-center justify-center">2</span><div><b>Accept the License Agreement</b> — read the terms and click <b>I Agree</b>.</div></li>
            <li class="flex gap-4"><span class="w-7 h-7 shrink-0 rounded-full bg-brand text-white font-bold text-sm flex items-center justify-center">3</span><div><b>Choose the install folder</b> — keep the default <code class="bg-slate-100 px-1.5 py-0.5 rounded text-sm">%LOCALAPPDATA%\Programs\BMyDesk Agent</code> or pick your own, then <b>Install</b>. No admin rights needed.</div></li>
            <li class="flex gap-4"><span class="w-7 h-7 shrink-0 rounded-full bg-brand text-white font-bold text-sm flex items-center justify-center">4</span><div><b>Finish</b> — desktop and Start Menu shortcuts are created. Leave <b>Run BMyDesk Agent</b> checked and click <b>Finish</b> — a window opens showing <b>your device code</b> (e.g. <code class="bg-slate-100 px-1.5 py-0.5 rounded">XK4M2N9P</code>).</div></li>
            <li class="flex gap-4"><span class="w-7 h-7 shrink-0 rounded-full bg-brand text-white font-bold text-sm flex items-center justify-center">5</span><div><b>That's it</b> — the agent sits in the system tray and can start with Windows (tray menu → <b>Start at login</b>). Keep the window open or close it — the code stays live.</div></li>
            <li class="flex gap-4"><span class="w-7 h-7 shrink-0 rounded-full bg-brand text-white font-bold text-sm flex items-center justify-center">6</span><div><b>Optional: unattended access</b> — set a PIN in the agent so you can connect later without anyone approving.</div></li>
        </ol>
        <div class="mt-8 p-4 rounded-xl bg-slate-50 border border-slate-200 text-sm">
            <div class="font-bold text-slate-800 mb-1"><i class="fas fa-shield-halved text-green-600 mr-1"></i>Verify your download</div>
            <p class="text-slate-600 mb-2">Only download from <code class="bg-slate-100 px-1 rounded">bmydesk.believoo.com</code>. The official v1.3.12 installer SHA-256 is:</p>
            <code class="block bg-slate-900 text-cyan-300 text-xs rounded-lg px-3 py-2 break-all select-all">9cfa0dbe0ce41f83a1be82c1d230649cd9c8d72458444716070a21cd5e8d899d</code>
            <p class="text-slate-500 text-xs mt-2">In PowerShell: <code class="bg-slate-100 px-1 rounded">Get-FileHash BMyDesk-Agent-Setup-1.3.12.exe</code></p>
        </div>
    </div>

    {{-- ANDROID --}}
    <div class="bg-white rounded-2xl border border-slate-100 p-8 mb-8">
        <div class="flex items-center gap-3 mb-6">
            <span class="w-10 h-10 rounded-xl bg-green-50 text-green-600 flex items-center justify-center text-xl"><i class="fab fa-android"></i></span>
            <h2 class="text-2xl font-bold text-slate-900">Android</h2>
        </div>
        <a href="/dl/agent.apk" class="inline-block px-6 py-3 bg-brand text-white font-bold rounded-xl mb-8"><i class="fas fa-download mr-2"></i>Download APK</a>
        <ol class="space-y-5">
            <li class="flex gap-4"><span class="w-7 h-7 shrink-0 rounded-full bg-brand text-white font-bold text-sm flex items-center justify-center">1</span><div><b>Allow the install</b> — if Play Protect warns, tap <b>More details → Install anyway</b> (or pause Play Protect in Play Store settings). The app isn't on Play Store yet so Google treats it as unknown.</div></li>
            <li class="flex gap-4"><span class="w-7 h-7 shrink-0 rounded-full bg-brand text-white font-bold text-sm flex items-center justify-center">2</span><div><b>Grant permissions</b> — allow <b>screen capture</b>, <b>accessibility</b> (for remote control) and <b>battery exemption</b> (so the code stays online).</div></li>
            <li class="flex gap-4"><span class="w-7 h-7 shrink-0 rounded-full bg-brand text-white font-bold text-sm flex items-center justify-center">3</span><div><b>Share your code</b> — the app shows a permanent code; it re-registers automatically after reboot.</div></li>
        </ol>
        <div class="mt-8 p-4 rounded-xl bg-slate-50 border border-slate-200 text-sm">
            <div class="font-bold text-slate-800 mb-1"><i class="fas fa-fingerprint text-green-600 mr-1"></i>Signed &amp; verified</div>
            <p class="text-slate-600 mb-2">The APK is signed with the official Believoo release certificate. Official v1.3.13 APK SHA-256:</p>
            <code class="block bg-slate-900 text-cyan-300 text-xs rounded-lg px-3 py-2 break-all select-all">d086767ecfcc752f9cb2f910fab4829997850c45f4434b5463858383bd06c6be</code>
        </div>
    </div>

    {{-- VIEWER --}}
    <div class="bg-slate-900 rounded-2xl p-8 text-white">
        <h2 class="text-2xl font-bold mb-4"><i class="fas fa-globe text-cyan-400 mr-2"></i>Connecting as a viewer</h2>
        <p class="text-slate-300 mb-6">No app needed. Enter the host's code below or share it as a link — <code class="bg-white/10 px-1.5 rounded">bmydesk.believoo.com/remote/guest/CODE</code></p>
        <form action="{{ route('bconnect.remote.guest') }}" method="GET" class="flex gap-3 max-w-md">
            <input name="code" maxlength="8" required placeholder="HOST CODE" class="flex-1 px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white font-mono uppercase tracking-widest placeholder-white/40 focus:outline-none focus:border-cyan-400">
            <button class="px-6 py-3 bg-cyan-500 hover:bg-cyan-400 text-white font-bold rounded-xl transition">Connect</button>
        </form>
    </div>

    <p class="text-center text-slate-500 text-sm mt-12">Need help? <a href="/help" class="text-brand font-semibold">Help center</a> · <a href="/support/portal" class="text-brand font-semibold">Open a support ticket</a></p>
</div>
</body>
</html>
