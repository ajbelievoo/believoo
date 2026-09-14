@extends('layouts.app')

@section('title', 'Migration #' . $migration->id . ' - Believoo')

@section('content')
<div class="container" style="max-width: 900px; margin: 40px auto; padding: 0 20px;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 2rem; font-weight: 700; color: #fff; margin-bottom: 8px;">
                <i class="fas fa-dolly" style="margin-right: 12px; color: #00b7ff;"></i>Migration #{{ $migration->id }}
            </h1>
            <p style="color: #8b9bb4;">{{ $migration->status_message }}</p>
        </div>
        <a href="{{ route('client.migration.index') }}" class="btn btn-secondary" style="padding: 12px 24px; border-radius: 12px;">
            <i class="fas fa-arrow-left" style="margin-right: 8px;"></i>Back
        </a>
    </div>

    <div class="grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 24px;">
        <div class="card" style="background: #111; border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 24px;">
            <p style="color: #8b9bb4; font-size: 0.8rem; margin-bottom: 6px;">Source IP</p>
            <p style="color: #fff; font-weight: 700; font-size: 1.1rem;">{{ $migration->source_ip }}</p>
        </div>
        <div class="card" style="background: #111; border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 24px;">
            <p style="color: #8b9bb4; font-size: 0.8rem; margin-bottom: 6px;">Source Port</p>
            <p style="color: #fff; font-weight: 700; font-size: 1.1rem;">{{ $migration->source_ssh_port ?? 22 }}</p>
        </div>
        <div class="card" style="background: #111; border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 24px;">
            <p style="color: #8b9bb4; font-size: 0.8rem; margin-bottom: 6px;">Status</p>
            <p style="color: #fff; font-weight: 700; font-size: 1.1rem;">{{ ucfirst($migration->status) }}</p>
        </div>
        <div class="card" style="background: #111; border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 24px;">
            <p style="color: #8b9bb4; font-size: 0.8rem; margin-bottom: 6px;">Progress</p>
            <p style="color: #fff; font-weight: 700; font-size: 1.1rem;">{{ $migration->progress_percent }}%</p>
        </div>
    </div>

    <div class="card" style="background: #111; border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 24px; margin-bottom: 24px;">
        <h3 style="color: #fff; margin-bottom: 16px; font-size: 1.1rem;">Progress</h3>
        <div style="background: #1a1a1a; border-radius: 10px; overflow: hidden; height: 16px;">
            <div style="width: {{ $migration->progress_percent }}%; background: linear-gradient(90deg, #00b7ff, #7000ff); height: 100%;"></div>
        </div>
        <p style="color: #94a3b8; font-size: 0.85rem; margin-top: 10px;">Last updated: {{ $migration->updated_at->format('M d, Y H:i') }}</p>
    </div>

    @if($migration->proxmoxVm)
    <div class="card" style="background: #111; border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 24px;">
        <h3 style="color: #fff; margin-bottom: 16px; font-size: 1.1rem;">Target VM</h3>
        <p style="color: #cbd5e1; margin-bottom: 8px;"><strong style="color: #fff;">VM ID:</strong> {{ $migration->proxmoxVm->vmid ?? '—' }}</p>
        <p style="color: #cbd5e1; margin-bottom: 8px;"><strong style="color: #fff;">Hostname:</strong> {{ $migration->proxmoxVm->hostname ?? '—' }}</p>
        <p style="color: #cbd5e1;"><strong style="color: #fff;">Node:</strong> {{ $migration->proxmoxVm->node ?? '—' }}</p>
    </div>
    @endif
</div>
@endsection
