@extends('layouts.admin')
@section('title', 'B-CONNECT Company')
@section('content')
<style>
    .bc-hero {
        background: linear-gradient(135deg, #0b1220 0%, #111827 40%, #0b1220 100%);
        border: 1px solid rgba(0,183,255,0.15);
        border-radius: 18px;
        padding: 24px 28px;
        margin-bottom: 24px;
        position: relative;
        overflow: hidden;
    }
    .bc-hero::before {
        content: "";
        position: absolute;
        top: -50%;
        right: -10%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(0,183,255,0.15) 0%, transparent 70%);
        border-radius: 50%;
    }
    .bc-hero h1 { font-size: 1.8rem; font-weight: 700; color: #f8fafc; margin: 0; }
    .bc-hero p { color: #94a3b8; margin: 6px 0 0; font-size: 0.9rem; }
    .bc-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.04);
    }
    .bc-detail {
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        border-bottom: 1px solid rgba(148,163,184,0.08);
    }
    .bc-detail-icon {
        width: 44px; height: 44px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem;
    }
    .bc-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 8px;
        font-size: 0.72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        background: rgba(0,183,255,0.12);
        color: #00b7ff;
    }
    .bc-back {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 600;
        color: #00b7ff;
        background: rgba(0,183,255,0.08);
        border: 1px solid rgba(0,183,255,0.2);
        text-decoration: none;
        transition: all 0.2s;
    }
    .bc-back:hover { background: rgba(0,183,255,0.15); }
</style>

<div class="bc-hero">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px;">
        <div style="display:flex; align-items:center; gap:14px;">
            <i class="fas fa-building" style="font-size:2rem;color:#00b7ff;"></i>
            <div>
                <h1>{{ $company->name }}</h1>
                <p>Company profile &amp; billing overview.</p>
            </div>
        </div>
        <a href="{{ route('admin.bconnect.index') }}" class="bc-back"><i class="fas fa-arrow-left"></i> Back to B-CONNECT</a>
    </div>
</div>

<div class="bc-card" style="margin-bottom:24px;">
    <div class="bc-detail">
        <div class="bc-detail-icon" style="background:rgba(0,183,255,0.12);color:#00b7ff;"><i class="fas fa-id-card"></i></div>
        <div>
            <div style="font-size:0.75rem;color:#64748b;text-transform:uppercase;letter-spacing:0.4px;">Plan</div>
            <div style="font-weight:700;font-size:1rem;color:var(--text-primary);margin-top:4px;"><span class="bc-badge">{{ ucfirst($company->plan) }}</span></div>
        </div>
    </div>
    <div class="bc-detail">
        <div class="bc-detail-icon" style="background:rgba(34,197,94,0.12);color:#22c55e;"><i class="fas fa-globe"></i></div>
        <div>
            <div style="font-size:0.75rem;color:#64748b;text-transform:uppercase;letter-spacing:0.4px;">Domain</div>
            <div style="font-weight:700;font-size:1rem;color:var(--text-primary);margin-top:4px;">{{ $company->domain ?: '—' }}</div>
        </div>
    </div>
    <div class="bc-detail">
        <div class="bc-detail-icon" style="background:rgba(168,85,247,0.12);color:#a855f7;"><i class="fas fa-circle-check"></i></div>
        <div>
            <div style="font-size:0.75rem;color:#64748b;text-transform:uppercase;letter-spacing:0.4px;">Status</div>
            <div style="font-weight:700;font-size:1rem;color:var(--text-primary);margin-top:4px;">
                {{ $company->is_active ? '<span style="color:#22c55e;">Active</span>' : '<span style="color:#64748b;">Inactive</span>' }}
            </div>
        </div>
    </div>
    <div class="bc-detail">
        <div class="bc-detail-icon" style="background:rgba(245,158,11,0.12);color:#f59e0b;"><i class="fas fa-calendar"></i></div>
        <div>
            <div style="font-size:0.75rem;color:#64748b;text-transform:uppercase;letter-spacing:0.4px;">Plan Expires</div>
            <div style="font-weight:700;font-size:1rem;color:var(--text-primary);margin-top:4px;">{{ $company->plan_expires_at ? $company->plan_expires_at->format('Y-m-d H:i') : '—' }}</div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start;">
    <div class="bc-card" style="padding:20px;">
        <h3 style="margin:0 0 16px;font-size:1.05rem;font-weight:700;">Actions</h3>

        <form method="POST" action="{{ route('admin.bconnect.company.plan', $company) }}" style="margin-bottom:16px;">@csrf @method('PUT')
            <div style="margin-bottom:8px;font-size:0.8rem;color:#64748b;">Plan</div>
            <div style="display:flex;gap:8px;">
                <select name="plan" class="form-control" style="flex:1;">
                    <option value="free" {{ $company->plan == 'free' ? 'selected' : '' }}>Free</option>
                    <option value="pro" {{ $company->plan == 'pro' ? 'selected' : '' }}>Pro</option>
                    <option value="enterprise" {{ $company->plan == 'enterprise' ? 'selected' : '' }}>Enterprise</option>
                </select>
            </div>
            <div style="margin-top:12px;">
                <div style="font-size:0.8rem;color:#64748b;margin-bottom:4px;">Plan Expires At</div>
                <input type="datetime-local" name="plan_expires_at" value="{{ $company->plan_expires_at ? $company->plan_expires_at->format('Y-m-d\TH:i') : '' }}" class="form-control" style="width:100%;">
            </div>
            <button type="submit" class="btn btn-primary btn-sm" style="margin-top:12px;">Update Plan</button>
        </form>

        <form method="POST" action="{{ route('admin.bconnect.company.toggle', $company) }}" style="display:inline;">@csrf @method('PUT')
            <button type="submit" class="btn {{ $company->is_active ? 'btn-danger' : 'btn-success' }} btn-sm" onclick="return confirm('{{ $company->is_active ? 'Suspend' : 'Activate' }} this company?')">
                {{ $company->is_active ? 'Suspend Company' : 'Activate Company' }}
            </button>
        </form>

        @if($company->domain)
        <form method="POST" action="{{ route('admin.bconnect.company.domain', $company) }}" style="display:inline;margin-left:8px;">@csrf
            <button type="submit" class="btn btn-info btn-sm" onclick="return confirm('Apply vhost for {{ $company->domain }}?')">Apply Domain</button>
        </form>
        @endif
    </div>

    <div class="bc-card" style="padding:20px;">
        <h3 style="margin:0 0 16px;font-size:1.05rem;font-weight:700;">Notify Admins</h3>
        <form method="POST" action="{{ route('admin.bconnect.company.notify', $company) }}">@csrf
            <input type="text" name="title" placeholder="Title" class="form-control" style="width:100%;margin-bottom:10px;" required>
            <textarea name="message" rows="3" placeholder="Message" class="form-control" style="width:100%;margin-bottom:10px;" required></textarea>
            <button type="submit" class="btn btn-primary btn-sm">Send Notification</button>
        </form>
    </div>
</div>

@if($invoices->count())
<div class="bc-card" style="margin-top:24px;padding:20px;">
    <h3 style="margin:0 0 16px;font-size:1.05rem;font-weight:700;">Recent Invoices</h3>
    <table class="table table-sm table-striped">
        <thead><tr><th>Number</th><th>Amount</th><th>Status</th><th>Created</th></tr></thead>
        <tbody>
            @foreach($invoices as $i)
            <tr>
                <td>{{ $i->invoice_number }}</td>
                <td>₹{{ number_format($i->amount, 2) }}</td>
                <td><span class="badge bg-{{ $i->status == 'paid' ? 'success' : 'warning' }}">{{ ucfirst($i->status) }}</span></td>
                <td>{{ $i->created_at->format('Y-m-d') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
