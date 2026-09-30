@extends('layouts.admin')

@section('title', 'Audit Logs')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Audit Logs</h1>
        <p class="page-subtitle">Track admin actions and system events</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-clipboard-list"></i>All Logs</div>
        <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="table-actions" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search logs..." class="form-input" style="min-width: 220px;">
            <select name="action" class="form-select" style="min-width: 160px;">
                <option value="">All Actions</option>
                @foreach($actions as $action)
                    <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $action)) }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-secondary">
                <i class="fas fa-filter"></i>Filter
            </button>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
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
                                <span style="font-weight: 600; color: var(--text-primary);">{{ $log->user->name }}</span>
                                <br><small style="color: var(--text-muted);">{{ $log->user->email }}</small>
                            @else
                                <span style="color: var(--text-muted);">System</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-{{ match($log->action) { 'created' => 'success', 'updated', 'admin_action_update' => 'info', 'deleted', 'admin_action_delete' => 'danger', default => 'slate' } }}">
                                {{ ucfirst(str_replace('_', ' ', $log->action)) }}
                            </span>
                        </td>
                        <td>
                            <p style="margin: 0; font-size: 0.875rem; color: var(--text-secondary);">{{ $log->description }}</p>
                            @if($log->auditable_type)
                                <small style="color: var(--text-muted);">{{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}</small>
                            @endif
                        </td>
                        <td>{{ $log->ip_address ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="empty-state"><i class="fas fa-clipboard-list"></i><div>No audit logs found</div></td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($logs->hasPages())
    <div class="card-footer">
        {{ $logs->links() }}
    </div>
    @endif
</div>
@endsection
