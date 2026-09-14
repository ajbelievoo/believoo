@extends('layouts.app')

@section('title', 'My Migrations - Believoo')

@section('content')
<div class="container" style="max-width: 1100px; margin: 40px auto; padding: 0 20px;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 2rem; font-weight: 700; color: #fff; margin-bottom: 8px;">
                <i class="fas fa-dolly" style="margin-right: 12px; color: #00b7ff;"></i>My Migrations
            </h1>
            <p style="color: #8b9bb4;">Track server migration progress.</p>
        </div>
        <a href="{{ route('client.migration.wizard') }}" class="btn btn-primary" style="padding: 12px 24px; border-radius: 12px;">
            <i class="fas fa-plus" style="margin-right: 8px;"></i>New Migration
        </a>
    </div>

    <div class="card" style="background: #111; border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 24px;">
        @if($migrations->isNotEmpty())
        <table style="width: 100%; color: #cbd5e1; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.08);">
                    <th style="text-align: left; padding: 12px;">ID</th>
                    <th style="text-align: left; padding: 12px;">Source IP</th>
                    <th style="text-align: left; padding: 12px;">VM</th>
                    <th style="text-align: left; padding: 12px;">Status</th>
                    <th style="text-align: left; padding: 12px;">Progress</th>
                    <th style="text-align: left; padding: 12px;">Started</th>
                    <th style="text-align: right; padding: 12px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($migrations as $migration)
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <td style="padding: 14px 12px; color: #fff; font-weight: 600;">#{{ $migration->id }}</td>
                    <td style="padding: 14px 12px;">{{ $migration->source_ip }}</td>
                    <td style="padding: 14px 12px;">{{ $migration->proxmoxVm?->vmid ?? '—' }}</td>
                    <td style="padding: 14px 12px;"><span class="badge badge-{{ in_array($migration->status, ['completed','success']) ? 'success' : ($migration->status === 'failed' ? 'danger' : 'warning') }}">{{ ucfirst($migration->status) }}</span></td>
                    <td style="padding: 14px 12px;">
                        <div style="background: #1a1a1a; border-radius: 6px; overflow: hidden; height: 8px; width: 120px;">
                            <div style="width: {{ $migration->progress_percent }}%; background: #00b7ff; height: 100%;"></div>
                        </div>
                        <span style="font-size: 0.75rem; color: #94a3b8;">{{ $migration->progress_percent }}%</span>
                    </td>
                    <td style="padding: 14px 12px; font-size: 0.85rem; color: #94a3b8;">{{ $migration->created_at->format('M d, Y H:i') }}</td>
                    <td style="padding: 14px 12px; text-align: right;">
                        <a href="{{ route('client.migration.show', $migration) }}" class="btn btn-sm btn-secondary" style="padding: 6px 12px; border-radius: 8px; font-size: 0.8rem;">View</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($migrations->hasPages())
        <div style="margin-top: 20px;">{{ $migrations->links() }}</div>
        @endif
        @else
        <div style="text-align: center; padding: 60px 20px;">
            <i class="fas fa-dolly" style="font-size: 3rem; color: #334155; margin-bottom: 16px;"></i>
            <h3 style="color: #fff; margin-bottom: 8px;">No migrations yet</h3>
            <p style="color: #64748b;">Start a migration to move your existing server to Believoo.</p>
            <a href="{{ route('client.migration.wizard') }}" class="btn btn-primary" style="margin-top: 16px; display: inline-block; padding: 12px 24px; border-radius: 10px;">Start Migration</a>
        </div>
        @endif
    </div>
</div>
@endsection
