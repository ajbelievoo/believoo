@extends('layouts.admin')

@section('title', 'Churn Risk')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Predictive Churn Risk</h1>
        <a href="{{ route('admin.churn-risk.index', ['tab' => $tab]) }}" class="btn btn-outline-secondary"><i class="fas fa-sync-alt me-1"></i> Refresh</a>
    </div>

    @include('admin.partials.alerts')

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-danger">
                <div class="card-body">
                    <div class="text-muted small">High Risk Users</div>
                    <div class="h4 text-danger">{{ number_format($counts['users_high']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-warning">
                <div class="card-body">
                    <div class="text-muted small">Medium Risk Users</div>
                    <div class="h4 text-warning">{{ number_format($counts['users_medium']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-danger">
                <div class="card-body">
                    <div class="text-muted small">High Risk B-Connect</div>
                    <div class="h4 text-danger">{{ number_format($counts['companies_high']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-warning">
                <div class="card-body">
                    <div class="text-muted small">Medium Risk B-Connect</div>
                    <div class="h4 text-warning">{{ number_format($counts['companies_medium']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'users' ? 'active' : '' }}" href="{{ route('admin.churn-risk.index', ['tab' => 'users']) }}">Users</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'bconnect' ? 'active' : '' }}" href="{{ route('admin.churn-risk.index', ['tab' => 'bconnect']) }}">B-Connect Companies</a>
        </li>
    </ul>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Score</th>
                            <th>Risk</th>
                            <th>Signals</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($tab === 'users')
                            @forelse($userRisks as $item)
                                <tr>
                                    <td>
                                        <strong>{{ $item['user']->name }}</strong><br>
                                        <span class="text-muted small">{{ $item['user']->email }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="fw-bold">{{ $item['score'] }}</span>
                                            <div class="progress" style="width: 80px; height: 6px;">
                                                <div class="progress-bar bg-{{ $item['risk'] === 'high' ? 'danger' : ($item['risk'] === 'medium' ? 'warning' : 'success') }}" style="width: {{ $item['score'] }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $item['risk'] === 'high' ? 'danger' : ($item['risk'] === 'medium' ? 'warning' : 'success') }}">{{ ucfirst($item['risk']) }}</span>
                                    </td>
                                    <td>
                                        <ul class="mb-0 ps-3 small">
                                            @foreach($item['signals'] as $signal)
                                                <li>{{ $signal }}</li>
                                            @endforeach
                                        </ul>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.users.edit', $item['user']->id) }}" class="btn btn-sm btn-outline-primary">Profile</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">No users found.</td></tr>
                            @endforelse
                        @else
                            @forelse($companyRisks as $item)
                                <tr>
                                    <td>
                                        <strong>{{ $item['company']->name }}</strong><br>
                                        <span class="text-muted small">{{ $item['company']->plan }} @if($item['company']->plan_expires_at) &bull; Expires {{ $item['company']->plan_expires_at->format('d M Y') }} @endif</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="fw-bold">{{ $item['score'] }}</span>
                                            <div class="progress" style="width: 80px; height: 6px;">
                                                <div class="progress-bar bg-{{ $item['risk'] === 'high' ? 'danger' : ($item['risk'] === 'medium' ? 'warning' : 'success') }}" style="width: {{ $item['score'] }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $item['risk'] === 'high' ? 'danger' : ($item['risk'] === 'medium' ? 'warning' : 'success') }}">{{ ucfirst($item['risk']) }}</span>
                                    </td>
                                    <td>
                                        <ul class="mb-0 ps-3 small">
                                            @foreach($item['signals'] as $signal)
                                                <li>{{ $signal }}</li>
                                            @endforeach
                                        </ul>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.bconnect.company', $item['company']->id) }}" class="btn btn-sm btn-outline-primary">Company</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">No B-Connect companies found.</td></tr>
                            @endforelse
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
