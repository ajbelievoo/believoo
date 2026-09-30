@extends('layouts.admin')

@section('title', 'Users')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Users</h1>
        <p class="page-subtitle">Manage all registered users</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-users"></i>All Users</div>
        <div class="page-actions">
            <a href="#" class="btn btn-primary"><i class="fas fa-plus"></i>Add User</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Orders</th>
                    <th>Agreements</th>
                    <th>Server</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div class="user-avatar" style="width: 36px; height: 36px; font-size: 0.85rem;">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div>
                                <div style="font-weight: 600; color: var(--text-primary);">{{ $user->name }}</div>
                                @if($user->is_admin)
                                    <span class="badge badge-info" style="font-size: 0.6rem; padding: 2px 6px;">Admin</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->orders_count }}</td>
                    <td>{{ $user->agreements_count }}</td>
                    <td>
                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            @if($user->whmcs_client_id || $user->vps_ids)
                                @if($user->whmcs_client_id)
                                    <span style="font-size: 0.75rem; color: var(--accent);">
                                        <i class="fas fa-check-circle" style="margin-right: 4px;"></i>WHMCS: {{ $user->whmcs_client_id }}
                                    </span>
                                @endif
                                @if($user->vps_ids)
                                    <span style="font-size: 0.75rem; color: var(--success);">
                                        <i class="fas fa-server" style="margin-right: 4px;"></i>VPS: {{ is_array($user->vps_ids) ? count($user->vps_ids) : 0 }} servers
                                    </span>
                                @endif
                            @else
                                <span style="font-size: 0.75rem; color: var(--text-muted);">
                                    <i class="fas fa-times-circle" style="margin-right: 4px;"></i>No Servers
                                </span>
                            @endif
                            @if($user->plan_price)
                                <span style="font-size: 0.75rem; color: {{ $user->billing_status === 'active' ? 'var(--success)' : ($user->billing_status === 'suspended' ? 'var(--danger)' : 'var(--text-muted)') }};">
                                    <i class="fas fa-{{ $user->billing_status === 'active' ? 'check' : ($user->billing_status === 'suspended' ? 'exclamation' : 'circle') }}" style="margin-right: 4px;"></i>
                                    ₹{{ number_format($user->plan_price, 0) }} - {{ ucfirst($user->billing_status ?? 'Unknown') }}
                                </span>
                            @endif
                        </div>
                    </td>
                    <td>{{ $user->created_at->format('M d, Y') }}</td>
                    <td>
                        <div style="display: flex; gap: 8px;">
                            <a href="{{ route('admin.users.show', $user) }}" class="btn btn-secondary btn-sm"><i class="fas fa-eye"></i></a>
                            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-secondary btn-sm"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="empty-state"><i class="fas fa-users"></i><div>No users found</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
    <div class="card-footer">
        {{ $users->links() }}
    </div>
    @endif
</div>
@endsection
