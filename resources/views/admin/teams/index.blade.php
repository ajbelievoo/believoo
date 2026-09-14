@extends('layouts.admin')

@section('title', 'Team Members')

@section('content')
<div class="page-header">
    <h1 class="page-title">Team Members</h1>
    <p class="page-subtitle">Manage your team</p>
</div>

<div class="data-table">
    <div class="table-header">
        <h3 class="table-title">All Members</h3>
        <div class="table-actions">
            <a href="{{ route('admin.teams.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Member
            </a>
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Member</th>
                <th>Role</th>
                <th>Active</th>
                <th>Order</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($teams as $member)
            <tr>
                <td>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <img src="{{ $member->photo_url }}" alt="{{ $member->name }}" loading="lazy" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover;">
                        <span style="font-weight: 600;">{{ $member->name }}</span>
                    </div>
                </td>
                <td>{{ $member->role ?? '—' }}</td>
                <td>
                    <span class="badge badge-{{ $member->is_active ? 'success' : 'danger' }}">
                        {{ $member->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td>{{ $member->order }}</td>
                <td>
                    <div style="display: flex; gap: 8px;">
                        <a href="{{ route('admin.teams.edit', $member) }}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem;">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('admin.teams.destroy', $member) }}" method="POST" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem; background: rgba(239,68,68,0.1); color: #ef4444; border-color: rgba(239,68,68,0.3);" onclick="return confirm('Delete this member?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center; padding: 60px; color: var(--text-muted);">
                    <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 16px; opacity: 0.3;"></i>
                    <p>No team members found</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($teams->hasPages())
    <div style="padding: 20px 24px; border-top: 1px solid var(--border-color);">
        {{ $teams->links() }}
    </div>
    @endif
</div>
@endsection
