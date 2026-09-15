@extends('layouts.admin')

@section('title', 'API Keys')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">API Keys</h1>
        <a href="{{ route('admin.api-keys.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> New API Key
        </a>
    </div>

    @include('admin.partials.alerts')

    @if (session('api_key_created') && session('api_key_plain'))
        <div class="alert alert-warning border-warning">
            <h5 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Copy this token now</h5>
            <p class="mb-2">This is the only time the full token for <strong>{{ session('api_key_name') }}</strong> will be shown.</p>
            <div class="input-group mb-2">
                <input type="text" id="new-api-key" class="form-control font-monospace" value="{{ session('api_key_plain') }}" readonly>
                <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('new-api-key').value)">
                    <i class="fas fa-copy"></i> Copy
                </button>
            </div>
            <p class="mb-0 small">Use it as <code>Authorization: Bearer &lt;token&gt;</code> or <code>X-API-Key: &lt;token&gt;</code>.</p>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="get" class="row g-3 mb-3">
                <div class="col-md-4">
                    <input type="text" name="q" class="form-control" placeholder="Search name, user, prefix" value="{{ request('q') }}">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-secondary w-100">Filter</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
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
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($keys as $key)
                            <tr>
                                <td>{{ $key->name }}</td>
                                <td>{{ $key->user?->name ?? '—' }}<br><small class="text-muted">{{ $key->user?->email }}</small></td>
                                <td><code>{{ $key->key_prefix }}…</code></td>
                                <td>
                                    @foreach(array_slice($key->scopes ?? [], 0, 3) as $scope)
                                        <span class="badge bg-secondary me-1">{{ $scope }}</span>
                                    @endforeach
                                    @if (count($key->scopes ?? []) > 3)
                                        <span class="badge bg-light text-dark">+{{ count($key->scopes) - 3 }}</span>
                                    @endif
                                </td>
                                <td>{{ $key->rate_limit }}/min</td>
                                <td>{{ number_format($key->requests_count) }}</td>
                                <td>{{ $key->last_used_at?->diffForHumans() ?? 'Never' }}</td>
                                <td>
                                    <span class="badge bg-{{ $key->is_active && (!$key->expires_at || !$key->expires_at->isPast()) ? 'success' : 'danger' }}">
                                        {{ $key->is_active && (!$key->expires_at || !$key->expires_at->isPast()) ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.api-keys.show', $key) }}" class="btn btn-sm btn-info">View</a>
                                    <a href="{{ route('admin.api-keys.edit', $key) }}" class="btn btn-sm btn-secondary">Edit</a>
                                    <form method="post" action="{{ route('admin.api-keys.regenerate', $key) }}" class="d-inline" onsubmit="return confirm('Regenerate token? The old token will stop working.')">
                                        @csrf
                                        @method('patch')
                                        <button class="btn btn-sm btn-warning">Regenerate</button>
                                    </form>
                                    <form method="post" action="{{ route('admin.api-keys.destroy', $key) }}" class="d-inline" onsubmit="return confirm('Revoke this API key?')">
                                        @csrf
                                        @method('delete')
                                        <button class="btn btn-sm btn-danger">Revoke</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted">No API keys found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $keys->links() }}
        </div>
    </div>
</div>
@endsection
