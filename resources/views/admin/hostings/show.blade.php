@extends('layouts.admin')
@section('title', 'Hosting — ' . $hosting->plan_name)
@section('content')

<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 28px; flex-wrap: wrap; gap: 12px;">
    <div>
        <a href="{{ route('admin.hostings.index') }}" style="font-size: 0.75rem; font-weight: 700; color: #00b7ff; text-transform: uppercase; letter-spacing: 0.1em; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 8px;">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        <h1 style="font-size: 1.5rem; font-weight: 900; color: var(--text-primary); margin: 0;">{{ $hosting->plan_name }}</h1>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 4px;">{{ $hosting->user->name ?? '—' }}</p>
    </div>
    <a href="{{ route('admin.hostings.edit', $hosting) }}" class="btn btn-primary">
        <i class="fas fa-edit"></i> Edit / Manage
    </a>
</div>

@if(session('success'))
    <div style="margin-bottom: 20px; padding: 14px 20px; background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.3); border-radius: 12px; color: #22c55e;">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
@endif

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    @php
        $items = [
            ['Status', ucfirst($hosting->status)],
            ['Plan', $hosting->plan_name],
            ['Type', ucfirst($hosting->hosting_type)],
            ['Client', $hosting->user->name ?? '—'],
            ['Server IP', $hosting->server_ip ?? '—'],
            ['IPv6', $hosting->ipv6 ?? '—'],
            ['Hostname', $hosting->server_hostname ?? '—'],
            ['Location', $hosting->datacenter_location ?? '—'],
            ['OS', $hosting->os_name ?? '—'],
            ['CPU', $hosting->cpu_cores ? $hosting->cpu_cores . ' vCores' : '—'],
            ['RAM', $hosting->ram_size ?? '—'],
            ['Storage', $hosting->storage_size ?? '—'],
            ['Expiry', $hosting->expiry_date?->format('M d, Y') ?? '—'],
            ['Control Panel', $hosting->control_panel_url ?? '—'],
        ];
    @endphp
    @foreach($items as [$label, $value])
        <div style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 14px; padding: 16px 20px;">
            <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 6px;">{{ $label }}</div>
            <div style="font-weight: 700; color: var(--text-primary);">{{ $value }}</div>
        </div>
    @endforeach
</div>
@endsection
