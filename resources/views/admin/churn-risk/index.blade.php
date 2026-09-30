@extends('layouts.admin')

@section('title', 'Churn Risk')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Churn Risk</h1>
        <p class="page-subtitle">Predictive churn risk for users and B-Connect companies</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.churn-risk.index', ['tab' => $tab]) }}" class="btn btn-secondary">
            <i class="fas fa-sync-alt"></i>Refresh
        </a>
    </div>
</div>

@include('admin.partials.alerts')

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-label">High Risk Users</div>
                <div class="stat-value" style="color: var(--danger);">{{ number_format($counts['users_high']) }}</div>
            </div>
            <div class="stat-icon red"><i class="fas fa-user-slash"></i></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-label">Medium Risk Users</div>
                <div class="stat-value" style="color: var(--warning);">{{ number_format($counts['users_medium']) }}</div>
            </div>
            <div class="stat-icon yellow"><i class="fas fa-user-clock"></i></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-label">High Risk B-Connect</div>
                <div class="stat-value" style="color: var(--danger);">{{ number_format($counts['companies_high']) }}</div>
            </div>
            <div class="stat-icon red"><i class="fas fa-building"></i></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-label">Medium Risk B-Connect</div>
                <div class="stat-value" style="color: var(--warning);">{{ number_format($counts['companies_medium']) }}</div>
            </div>
            <div class="stat-icon yellow"><i class="fas fa-building"></i></div>
        </div>
    </div>
</div>

<ul class="nav nav-pills mb-3">
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'users' ? 'active' : '' }}" href="{{ route('admin.churn-risk.index', ['tab' => 'users']) }}">Users</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'bconnect' ? 'active' : '' }}" href="{{ route('admin.churn-risk.index', ['tab' => 'bconnect']) }}">B-Connect Companies</a>
    </li>
</ul>

<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-chart-line"></i>Risk Overview</div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Score</th>
                        <th>Risk</th>
                        <th>Signals</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @if($tab === 'users')
                        @forelse($userRisks as $item)
                            <tr>
                                <td>
                                    <strong style="color: var(--text-primary);">{{ $item['user']->name }}</strong><br>
                                    <small style="color: var(--text-muted);">{{ $item['user']->email }}</small>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <span style="font-weight: 700; color: var(--text-primary);">{{ $item['score'] }}</span>
                                        <div style="width: 80px; height: 6px; background: var(--border-color); border-radius: 999px; overflow: hidden;">
                                            <div style="height: 100%; width: {{ $item['score'] }}%; background: {{ $item['risk'] === 'high' ? 'var(--danger)' : ($item['risk'] === 'medium' ? 'var(--warning)' : 'var(--success)') }}; border-radius: 999px;"></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $item['risk'] === 'high' ? 'danger' : ($item['risk'] === 'medium' ? 'warning' : 'success') }}">{{ ucfirst($item['risk']) }}</span>
                                </td>
                                <td>
                                    <ul style="margin: 0; padding-left: 16px; font-size: 0.85rem; color: var(--text-secondary);">
                                        @foreach($item['signals'] as $signal)
                                            <li>{{ $signal }}</li>
                                        @endforeach
                                    </ul>
                                </td>
                                <td style="text-align: right;">
                                    <a href="{{ route('admin.users.edit', $item['user']->id) }}" class="btn btn-sm btn-secondary">Profile</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="empty-state"><i class="fas fa-user"></i><div>No users found</div></td></tr>
                        @endforelse
                    @else
                        @forelse($companyRisks as $item)
                            <tr>
                                <td>
                                    <strong style="color: var(--text-primary);">{{ $item['company']->name }}</strong><br>
                                    <small style="color: var(--text-muted);">{{ $item['company']->plan }} @if($item['company']->plan_expires_at) &bull; Expires {{ $item['company']->plan_expires_at->format('d M Y') }} @endif</small>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <span style="font-weight: 700; color: var(--text-primary);">{{ $item['score'] }}</span>
                                        <div style="width: 80px; height: 6px; background: var(--border-color); border-radius: 999px; overflow: hidden;">
                                            <div style="height: 100%; width: {{ $item['score'] }}%; background: {{ $item['risk'] === 'high' ? 'var(--danger)' : ($item['risk'] === 'medium' ? 'var(--warning)' : 'var(--success)') }}; border-radius: 999px;"></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $item['risk'] === 'high' ? 'danger' : ($item['risk'] === 'medium' ? 'warning' : 'success') }}">{{ ucfirst($item['risk']) }}</span>
                                </td>
                                <td>
                                    <ul style="margin: 0; padding-left: 16px; font-size: 0.85rem; color: var(--text-secondary);">
                                        @foreach($item['signals'] as $signal)
                                            <li>{{ $signal }}</li>
                                        @endforeach
                                    </ul>
                                </td>
                                <td style="text-align: right;">
                                    <a href="{{ route('admin.bconnect.company', $item['company']->id) }}" class="btn btn-sm btn-secondary">Company</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="empty-state"><i class="fas fa-building"></i><div>No B-Connect companies found</div></td></tr>
                        @endforelse
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
