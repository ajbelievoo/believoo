@extends('layouts.admin')

@section('title', 'API Key: ' . $apiKey->name)

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">{{ $apiKey->name }}</h1>
        <a href="{{ route('admin.api-keys.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header">Details</div>
                <div class="card-body">
                    <p><strong>Owner:</strong> {{ $apiKey->user?->name ?? '—' }} ({{ $apiKey->user?->email }})</p>
                    <p><strong>Prefix:</strong> <code>{{ $apiKey->key_prefix }}…</code></p>
                    <p><strong>Rate Limit:</strong> {{ $apiKey->rate_limit }}/min</p>
                    <p><strong>Requests:</strong> {{ number_format($apiKey->requests_count) }}</p>
                    <p><strong>Last Used:</strong> {{ $apiKey->last_used_at?->diffForHumans() ?? 'Never' }}</p>
                    <p><strong>Created:</strong> {{ $apiKey->created_at->format('d M Y H:i') }}</p>
                    <p><strong>Expires:</strong> {{ $apiKey->expires_at?->format('d M Y H:i') ?? 'Never' }}</p>
                    <p><strong>Status:</strong>
                        <span class="badge bg-{{ $apiKey->is_active && (!$apiKey->expires_at || !$apiKey->expires_at->isPast()) ? 'success' : 'danger' }}">
                            {{ $apiKey->is_active && (!$apiKey->expires_at || !$apiKey->expires_at->isPast()) ? 'Active' : 'Inactive' }}
                        </span>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header">Scopes</div>
                <div class="card-body">
                    @forelse($apiKey->scopes ?? [] as $scope)
                        <span class="badge bg-secondary me-1 mb-1">{{ $scope }}</span>
                    @empty
                        <p class="text-muted">No scopes restricted (full access if active).</p>
                    @endforelse
                </div>
            </div>
            <div class="card shadow-sm mt-4">
                <div class="card-header">IP Restrictions</div>
                <div class="card-body">
                    @if (!empty($apiKey->allowed_ips))
                        <ul class="mb-0">
                            @foreach($apiKey->allowed_ips as $ip)
                                <li><code>{{ $ip }}</code></li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted">No IP restrictions.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
