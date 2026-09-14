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

<div class="bc-card">
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
</div>
@endsection
