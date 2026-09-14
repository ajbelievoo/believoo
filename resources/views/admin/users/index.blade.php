@extends('layouts.admin')

@section('title', 'Users')

@section('content')
<div class="page-header">
    <h1 class="page-title">Users</h1>
    <p class="page-subtitle">Manage all registered users</p>
</div>

<div class="data-table">
    <div class="table-header">
        <h3 class="table-title">All Users</h3>
        <div class="table-actions">
            <a href="#" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add User
            </a>
        </div>
    </div>
    <table>
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
                        <div style="width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #00b7ff, #0099ff); display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.85rem;">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <div>
                            <div style="font-weight: 600; color: #ffffff;">{{ $user->name }}</div>
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
                                <span style="font-size: 0.75rem; color: #00b7ff;">
                                    <i class="fas fa-check-circle" style="margin-right: 4px;"></i>WHMCS: {{ $user->whmcs_client_id }}
                                </span>
                            @endif
                            @if($user->vps_ids)
                                <span style="font-size: 0.75rem; color: #22c55e;">
                                    <i class="fas fa-server" style="margin-right: 4px;"></i>VPS: {{ is_array($user->vps_ids) ? count($user->vps_ids) : 0 }} servers
                                </span>
                            @endif
                        @else
                            <span style="font-size: 0.75rem; color: #6b7280;">
                                <i class="fas fa-times-circle" style="margin-right: 4px;"></i>No Servers
                            </span>
                        @endif
                        @if($user->plan_price)
                            <span style="font-size: 0.75rem; 
                                {{ $user->billing_status === 'active' ? 'color: #22c55e;' : ($user->billing_status === 'suspended' ? 'color: #ef4444;' : 'color: #8b9bb4;') }}">
                                <i class="fas fa-{{ $user->billing_status === 'active' ? 'check' : ($user->billing_status === 'suspended' ? 'exclamation' : 'circle') }}" style="margin-right: 4px;"></i>
                                ₹{{ number_format($user->plan_price, 0) }} - {{ ucfirst($user->billing_status ?? 'Unknown') }}
                            </span>
                        @endif
                    </div>
                </td>
                <td>{{ $user->created_at->format('M d, Y') }}</td>
                <td>
                    <div style="display: flex; gap: 8px;">
                        <a href="{{ route('admin.users.show', $user) }}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem;">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem;">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem; background: rgba(239,68,68,0.1); color: #ef4444; border-color: rgba(239,68,68,0.3);" onclick="return confirm('Are you sure?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 60px; color: rgba(255,255,255,0.5);">
                    <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 16px; opacity: 0.3;"></i>
                    <p>No users found</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($users->hasPages())
    <div style="padding: 20px 24px; border-top: 1px solid rgba(255,255,255,0.06);">
        {{ $users->links() }}
    </div>
    @endif
</div>
@endsection
