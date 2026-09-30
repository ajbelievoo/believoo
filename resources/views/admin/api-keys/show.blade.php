@extends('layouts.admin')

@section('title', 'API Key: ' . $apiKey->name)

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ $apiKey->name }}</h1>
        <p class="page-subtitle">API key details and restrictions</p>
    </div>
    <a href="{{ route('admin.api-keys.index') }}" class="btn btn-secondary">Back</a>
</div>

<div class="grid grid-cols-2">
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-info-circle"></i>Details</div>
        </div>
        <div class="card-body">
            <p><strong>Owner:</strong> {{ $apiKey->user?->name ?? '—' }} ({{ $apiKey->user?->email }})</p>
            <p><strong>Prefix:</strong> <code>{{ $apiKey->key_prefix }}…</code></p>
            <p><strong>Rate Limit:</strong> {{ $apiKey->rate_limit }}/min</p>
            <p><strong>Requests:</strong> {{ number_format($apiKey->requests_count) }}</p>
            <p><strong>Last Used:</strong> {{ $apiKey->last_used_at?->diffForHumans() ?? 'Never' }}</p>
            <p><strong>Created:</strong> {{ $apiKey->created_at->format('d M Y H:i') }}</p>
            <p><strong>Expires:</strong> {{ $apiKey->expires_at?->format('d M Y H:i') ?? 'Never' }}</p>
            <p><strong>Status:</strong>
                <span class="badge badge-{{ $apiKey->is_active && (!$apiKey->expires_at || !$apiKey->expires_at->isPast()) ? 'success' : 'danger' }}">
                    {{ $apiKey->is_active && (!$apiKey->expires_at || !$apiKey->expires_at->isPast()) ? 'Active' : 'Inactive' }}
                </span>
            </p>
        </div>
    </div>
    <div>
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-lock"></i>Scopes</div>
            </div>
            <div class="card-body">
                @forelse($apiKey->scopes ?? [] as $scope)
                    <span class="badge badge-slate">{{ $scope }}</span>
                @empty
                    <p style="color: var(--text-muted);">No scopes restricted (full access if active).</p>
                @endforelse
            </div>
        </div>
        <div class="card" style="margin-top: 16px;">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-network-wired"></i>IP Restrictions</div>
            </div>
            <div class="card-body">
                @if (!empty($apiKey->allowed_ips))
                    <ul style="margin: 0; padding-left: 18px;">
                        @foreach($apiKey->allowed_ips as $ip)
                            <li><code>{{ $ip }}</code></li>
                        @endforeach
                    </ul>
                @else
                    <p style="color: var(--text-muted);">No IP restrictions.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
