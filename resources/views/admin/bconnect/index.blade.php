@extends('layouts.admin')
@section('title', 'B-CONNECT Super Admin')
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fas fa-rocket" style="color:var(--admin-accent);margin-right:10px;"></i>B-CONNECT Super Admin</h1>
        <p class="page-subtitle">Unified brand and company management inside Believoo.</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.settings.index') }}?tab=bconnect" class="btn btn-secondary"><i class="fas fa-cog"></i>Brand & SEO Settings</a>
        <a href="{{ url('/panel') }}" class="btn btn-secondary"><i class="fas fa-tools"></i>Advanced Tools</a>
    </div>
</div>

<div class="stats-grid mb-6">
    <div class="stat-card">
        <div class="stat-header"><div class="stat-icon blue"><i class="fas fa-building"></i></div></div>
        <div class="stat-label">Companies</div>
        <div class="stat-value">{{ $companies->total() }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-header"><div class="stat-icon green"><i class="fas fa-rupee-sign"></i></div></div>
        <div class="stat-label">Total Revenue</div>
        <div class="stat-value">₹{{ number_format($revenue, 2) }}</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-list"></i>Companies</div>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Company</th>
                    <th>Plan</th>
                    <th>Projects</th>
                    <th>Members</th>
                    <th>Tickets</th>
                    <th>Invoices</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($companies as $c)
                <tr>
                    <td style="font-weight:600;">{{ $c->name }}</td>
                    <td><span class="badge badge-info">{{ ucfirst($c->plan) }}</span></td>
                    <td>{{ $c->projects_count }}</td>
                    <td>{{ $c->members_count }}</td>
                    <td>{{ $c->tickets_count }}</td>
                    <td>{{ $c->invoices_count }}</td>
                    <td><a href="{{ route('admin.bconnect.company', $c->id) }}" class="btn btn-primary btn-sm">View</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($companies->hasPages())
    <div class="card-footer">
        {{ $companies->links() }}
    </div>
    @endif
</div>
@endsection
