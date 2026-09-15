@extends('layouts.admin')

@section('title', 'Audit Logs')

@section('content')
<div class="page-header">
    <h1 class="page-title">Audit Logs</h1>
    <p class="page-subtitle">Track admin actions and system events</p>
</div>

<div class="data-table">
    <div class="table-header">
        <h3 class="table-title">All Logs</h3>
        <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="table-actions" style="display: flex; gap: 12px; align-items: center;">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search logs..." class="form-control" style="width: 220px;">
            <select name="action" class="form-control" style="width: 160px;">
                <option value="">All Actions</option>
                @foreach($actions as $action)
                    <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $action)) }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-secondary" style="padding: 8px 14px;">
                <i class="fas fa-filter"></i> Filter
            </button>
        </form>
    </div>
    <table>
        <thead>
            <tr>
                <th>Time</th>
                <th>User</th>
                <th>Action</th>
                <th>Description</th>
                <th>IP Address</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
            <tr>
                <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                <td>
                    @if($log->user)
                        <span style="font-weight: 600;">{{ $log->user->name }}</span>
                        <br><small style="color: var(--text-muted);">{{ $log->user->email }}</small>
                    @else
                        <span style="color: var(--text-muted);">System</span>
                    @endif
                </td>
                <td>
                    <span class="badge badge-{{ match($log->action) { 'created' => 'success', 'updated', 'admin_action_update' => 'info', 'deleted', 'admin_action_delete' => 'danger', default => 'secondary' } }}">
                        {{ ucfirst(str_replace('_', ' ', $log->action)) }}
                    </span>
                </td>
                <td>
                    <p style="margin: 0; font-size: 0.875rem;">{{ $log->description }}</p>
                    @if($log->auditable_type)
                        <small style="color: var(--text-muted);">{{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}</small>
                    @endif
                </td>
                <td>{{ $log->ip_address ?? '—' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center; padding: 60px; color: var(--text-muted);">
                    <i class="fas fa-clipboard-list" style="font-size: 3rem; margin-bottom: 16px; opacity: 0.3;"></i>
                    <p>No audit logs found</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($logs->hasPages())
    <div style="padding: 20px 24px; border-top: 1px solid var(--border-color);">
        {{ $logs->links() }}
    </div>
    @endif
</div>
@endsection
