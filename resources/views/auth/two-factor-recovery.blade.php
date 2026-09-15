<x-guest-layout>
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-amber-100 text-amber-600 mb-4">
            <i class="fas fa-life-ring text-2xl"></i>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 mb-1">Recovery Codes</h1>
        <p class="text-slate-500 text-sm">Save these codes in a safe place. Each code can be used once.</p>
    </div>

    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 mb-6">
        <div class="grid grid-cols-2 gap-3">
            @foreach ($codes as $code)
                <code class="block p-3 bg-white rounded-lg text-center text-sm font-mono text-slate-700 tracking-wider border border-amber-100 select-all">{{ $code }}</code>
            @endforeach
        </div>
    </div>

    <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 mb-6 text-sm text-slate-600">
        <p class="flex items-start gap-2">
            <i class="fas fa-info-circle mt-0.5 text-amber-500"></i>
            <span>These recovery codes are shown only once. Download or copy them before leaving this page.</span>
        </p>
    </div>

    <a href="{{ url()->previous() == url()->current() ? route('client.dashboard') : url()->previous() }}" class="w-full py-3.5 px-6 rounded-xl bg-amber-500 text-white font-bold text-sm hover:bg-amber-600 transition-colors shadow-md shadow-amber-500/20 inline-flex items-center justify-center gap-2">
        <i class="fas fa-arrow-right"></i>
        Continue
    </a>
</x-guest-layout>
