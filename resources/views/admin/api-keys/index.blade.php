@extends('layouts.admin')

@section('title', 'API Keys')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">API Keys</h1>
        <p class="page-subtitle">Manage REST API access tokens</p>
    </div>
    <a href="{{ route('admin.api-keys.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i>New API Key
    </a>
</div>

@include('admin.partials.alerts')

@if (session('api_key_created') && session('api_key_plain'))
    <div class="alert" style="background: var(--admin-warning-soft); color: var(--admin-warning); border: 1px solid rgba(245, 158, 11, 0.2);">
        <i class="fas fa-exclamation-triangle"></i>
        <div>
            <strong>Copy this token now</strong>
            <p style="margin: 4px 0 10px;">This is the only time the full token for <strong>{{ session('api_key_name') }}</strong> will be shown.</p>
            <div style="display: flex; gap: 8px;">
                <input type="text" id="new-api-key" class="form-input font-monospace" value="{{ session('api_key_plain') }}" readonly style="flex: 1;">
                <button type="button" class="btn btn-secondary" onclick="navigator.clipboard.writeText(document.getElementById('new-api-key').value)">
                    <i class="fas fa-copy"></i> Copy
                </button>
            </div>
            <p style="margin: 10px 0 0; font-size: 0.85rem;">Use it as <code>Authorization: Bearer &lt;token&gt;</code> or <code>X-API-Key: &lt;token&gt;</code>.</p>
        </div>
    </div>
@endif

<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-key"></i>All API Keys</div>
        <form method="get" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <input type="text" name="q" class="form-input" placeholder="Search name, user, prefix" value="{{ request('q') }}" style="min-width: 220px;">
            <select name="status" class="form-select" onchange="this.form.submit()" style="width: auto; min-width: 140px;">
                <option value="">All Statuses</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
            <button class="btn btn-secondary">Filter</button>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>User</th>
                        <th>Prefix</th>
                        <th>Scopes</th>
                        <th>Rate Limit</th>
                        <th>Requests</th>
                        <th>Last Used</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($keys as $key)
                        <tr>
                            <td style="font-weight: 600;">{{ $key->name }}</td>
                            <td>{{ $key->user?->name ?? '—' }}<br><small style="color: var(--text-muted);">{{ $key->user?->email }}</small></td>
                            <td><code>{{ $key->key_prefix }}…</code></td>
                            <td>
                                @foreach(array_slice($key->scopes ?? [], 0, 3) as $scope)
                                    <span class="badge badge-slate">{{ $scope }}</span>
                                @endforeach
                                @if (count($key->scopes ?? []) > 3)
                                    <span class="badge badge-slate">+{{ count($key->scopes) - 3 }}</span>
                                @endif
                            </td>
                            <td>{{ $key->rate_limit }}/min</td>
                            <td>{{ number_format($key->requests_count) }}</td>
                            <td>{{ $key->last_used_at?->diffForHumans() ?? 'Never' }}</td>
                            <td>
                                <span class="badge badge-{{ $key->is_active && (!$key->expires_at || !$key->expires_at->isPast()) ? 'success' : 'danger' }}">
                                    {{ $key->is_active && (!$key->expires_at || !$key->expires_at->isPast()) ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="{{ route('admin.api-keys.show', $key) }}" class="btn btn-secondary btn-sm">View</a>
                                <a href="{{ route('admin.api-keys.edit', $key) }}" class="btn btn-secondary btn-sm">Edit</a>
                                <form method="post" action="{{ route('admin.api-keys.regenerate', $key) }}" style="display: inline;" onsubmit="return confirm('Regenerate token? The old token will stop working.')">
                                    @csrf
                                    @method('patch')
                                    <button class="btn btn-primary btn-sm">Regenerate</button>
                                </form>
                                <form method="post" action="{{ route('admin.api-keys.destroy', $key) }}" style="display: inline;" onsubmit="return confirm('Revoke this API key?')">
                                    @csrf
                                    @method('delete')
                                    <button class="btn btn-danger btn-sm">Revoke</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="empty-state"><i class="fas fa-key"></i><div>No API keys found.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($keys->hasPages())
    <div class="card-footer">
        {{ $keys->links() }}
    </div>
    @endif
</div>
@endsection
