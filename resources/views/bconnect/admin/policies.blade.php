@extends('bconnect.layout')
@section('title', 'Remote Policies')
@section('content')
<div class="bc-card p-6 max-w-xl">
    <h3 class="font-bold mb-2">Remote-access policies</h3>
    <p class="text-xs text-slate-500 mb-6">Applied to all remote sessions under this company.</p>
    @php $pol = $company->remote_policies ?? []; @endphp
    <form method="POST" action="{{ route('bconnect.admin.policies.save') }}" class="space-y-4">@csrf
        @foreach([
            'file_transfer' => 'Allow file transfer in sessions',
            'clipboard' => 'Allow clipboard sync',
            'unattended' => 'Allow unattended (PIN) access',
            'browser_host' => 'Allow members to host from the browser',
            'session_recording' => 'Allow session recording',
        ] as $k => $label)
        <label class="flex items-center justify-between bg-slate-800/40 rounded-lg p-3.5 cursor-pointer">
            <span class="text-sm">{{ $label }}</span>
            <input type="checkbox" name="{{ $k }}" value="1" class="accent-cyan-500 w-4 h-4" {{ ($pol[$k] ?? true) ? 'checked' : '' }}>
        </label>
        @endforeach
        <button class="bc-btn bc-btn-primary">Save policies</button>
    </form>
</div>
@endsection
