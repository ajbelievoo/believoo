@extends('layouts.admin')
@section('title', 'Hosting Plans')
@section('content')

<div class="page-header">
    <h1 class="page-title">Hosting Plans</h1>
    <p class="page-subtitle">Manage all client hosting subscriptions</p>
</div>

@if(session('success'))
    <div style="margin-bottom: 20px; padding: 14px 20px; background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.3); border-radius: 12px; color: #22c55e; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
@endif

<div class="card">
    <div class="card-header">
    <div class="table-header">
        <h3 class="table-title">All Hosting Plans</h3>
        <div class="table-actions">
            <span style="font-size: 0.8rem; color: var(--text-muted);">{{ $hostings->total() }} total</span>
        </div>
    </div>
    <div class="card-body"><div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>Client</th>
                <th>Plan</th>
                <th>Type</th>
                <th>Status</th>
                <th>Server IP</th>
                <th>Expiry</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($hostings as $hosting)
            <tr>
                <td>
                    <div style="font-weight: 600;">{{ $hosting->user->name ?? '—' }}</div>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $hosting->user->email ?? '' }}</div>
                </td>
                <td>
                    <div style="font-weight: 600;">{{ $hosting->plan_name }}</div>
                    @if($hosting->order)
                        <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $hosting->order->order_number }}</div>
                    @endif
                </td>
                <td><span style="text-transform: capitalize;">{{ $hosting->hosting_type }}</span></td>
                <td>
                    @php
                        $colors = ['active'=>'success','pending'=>'info','suspended'=>'danger','cancelled'=>'danger','expired'=>'danger'];
                    @endphp
                    <span class="badge badge-{{ $colors[$hosting->status] ?? 'warning' }}">{{ ucfirst($hosting->status) }}</span>
                </td>
                <td style="font-family: monospace; font-size: 0.85rem;">{{ $hosting->server_ip ?? '—' }}</td>
                <td>
                    @if($hosting->expiry_date)
                        <span style="{{ $hosting->isExpiringSoon() ? 'color: #ef4444;' : '' }}">
                            {{ $hosting->expiry_date->format('M d, Y') }}
                        </span>
                    @else —
                    @endif
                </td>
                <td>
                    <div style="display: flex; gap: 8px;">
                        <a href="{{ route('admin.hostings.edit', $hosting) }}" class="btn btn-primary" style="padding: 6px 14px; font-size: 0.8rem;">
                            <i class="fas fa-edit"></i> Manage
                        </a>
                        <a href="{{ route('admin.hostings.show', $hosting) }}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem;">
                            <i class="fas fa-eye"></i>
                        </a>
                        <form action="{{ route('admin.hostings.destroy', $hosting) }}" method="POST" onsubmit="return confirm('Are you sure? This will permanently delete this hosting plan.');" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.8rem; background: #ef4444; border-color: #ef4444;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 60px; color: var(--text-muted);">
                    <i class="fas fa-server" style="font-size: 2rem; margin-bottom: 12px; opacity: 0.3; display: block;"></i>
                    No hosting plans found
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if($hostings->hasPages())
        <div style="padding: 20px 24px; border-top: 1px solid var(--border-color);">
            {{ $hostings->links() }}
        </div>
    @endif
</div>
@endsection
