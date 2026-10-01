@extends('layouts.admin')
@section('title', 'Bmydesk Company')
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fas fa-building" style="color:var(--admin-accent);margin-right:10px;"></i>{{ $company->name }}</h1>
        <p class="page-subtitle">Company profile, plan and billing overview.</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.bconnect.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i>Back to Bmydesk</a>
    </div>
</div>

<div class="grid grid-cols-2" style="grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-header"><div class="stat-icon blue"><i class="fas fa-id-card"></i></div></div>
        <div class="stat-label">Plan</div>
        <div class="stat-value" style="font-size:1.3rem;"><span class="badge badge-info">{{ ucfirst($company->plan) }}</span></div>
    </div>
    <div class="stat-card">
        <div class="stat-header"><div class="stat-icon purple"><i class="fas fa-globe"></i></div></div>
        <div class="stat-label">Domain</div>
        <div class="stat-value" style="font-size:1.1rem;">{{ $company->domain ?: '—' }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-header"><div class="stat-icon {{ $company->is_active ? 'green' : 'slate' }}"><i class="fas fa-circle-check"></i></div></div>
        <div class="stat-label">Status</div>
        <div class="stat-value" style="font-size:1.1rem;">
            @if($company->is_active)
                <span style="color:var(--admin-success);">Active</span>
            @else
                <span class="text-muted">Inactive</span>
            @endif
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-header"><div class="stat-icon yellow"><i class="fas fa-calendar"></i></div></div>
        <div class="stat-label">Plan Expires</div>
        <div class="stat-value" style="font-size:1.1rem;">{{ $company->plan_expires_at ? $company->plan_expires_at->format('Y-m-d H:i') : '—' }}</div>
    </div>
</div>

<div class="grid grid-cols-2" style="grid-template-columns:repeat(auto-fit, minmax(320px, 1fr));margin-bottom:24px;">
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-sliders-h"></i>Actions</div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.bconnect.company.plan', $company) }}" style="margin-bottom:18px;">@csrf @method('PUT')
                <div class="form-group">
                    <label class="form-label">Plan</label>
                    <select name="plan" class="form-select">
                        <option value="free" {{ $company->plan == 'free' ? 'selected' : '' }}>Free</option>
                        <option value="pro" {{ $company->plan == 'pro' ? 'selected' : '' }}>Pro</option>
                        <option value="enterprise" {{ $company->plan == 'enterprise' ? 'selected' : '' }}>Enterprise</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Plan Expires At</label>
                    <input type="datetime-local" name="plan_expires_at" value="{{ $company->plan_expires_at ? $company->plan_expires_at->format('Y-m-d\TH:i') : '' }}" class="form-input">
                </div>
                <button type="submit" class="btn btn-primary">Update Plan</button>
            </form>

            <form method="POST" action="{{ route('admin.bconnect.company.toggle', $company) }}" style="display:inline;">@csrf @method('PUT')
                <button type="submit" class="btn {{ $company->is_active ? 'btn-danger' : 'btn-primary' }}" onclick="return confirm('{{ $company->is_active ? 'Suspend' : 'Activate' }} this company?')">
                    {{ $company->is_active ? 'Suspend Company' : 'Activate Company' }}
                </button>
            </form>

            @if($company->domain)
            <form method="POST" action="{{ route('admin.bconnect.company.domain', $company) }}" style="display:inline;margin-left:8px;">@csrf
                <button type="submit" class="btn btn-secondary" onclick="return confirm('Apply vhost for {{ $company->domain }}?')">Apply Domain</button>
            </form>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-bell"></i>Notify Admins</div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.bconnect.company.notify', $company) }}">@csrf
                <div class="form-group">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" placeholder="Notification title" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Message</label>
                    <textarea name="message" rows="3" placeholder="Message" class="form-textarea" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Send Notification</button>
            </form>
        </div>
    </div>
</div>

@if($invoices->count())
<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-file-invoice-dollar"></i>Recent Invoices</div>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead><tr><th>Number</th><th>Amount</th><th>Status</th><th>Created</th></tr></thead>
            <tbody>
                @foreach($invoices as $i)
                <tr>
                    <td>{{ $i->invoice_number }}</td>
                    <td>₹{{ number_format($i->amount, 2) }}</td>
                    <td><span class="badge badge-{{ $i->status == 'paid' ? 'success' : 'warning' }}">{{ ucfirst($i->status) }}</span></td>
                    <td>{{ $i->created_at->format('Y-m-d') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
