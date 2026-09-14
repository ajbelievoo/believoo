@extends('layouts.admin')
@section('title', 'B-CONNECT Super Admin')
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
    .bc-stat {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        transition: transform 0.2s;
    }
    .bc-stat:hover { transform: translateY(-2px); }
    .bc-stat-icon {
        width: 52px; height: 52px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem;
    }
    .bc-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.04);
    }
    .bc-table { width: 100%; border-collapse: collapse; }
    .bc-table th {
        text-align: left;
        padding: 14px 16px;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        border-bottom: 1px solid var(--border-color);
        background: rgba(148,163,184,0.03);
    }
    .bc-table td {
        padding: 14px 16px;
        font-size: 0.85rem;
        color: var(--text-primary);
        border-bottom: 1px solid rgba(148,163,184,0.08);
    }
    .bc-table tr:hover td { background: rgba(0,183,255,0.03); }
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
    .bc-btn {
        padding: 8px 16px;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .bc-btn-primary {
        background: linear-gradient(135deg, #00b7ff, #0099ff);
        color: white;
    }
    .bc-btn-primary:hover { opacity: 0.92; text-decoration: none; }
</style>

<div class="bc-hero">
    <div style="display:flex; align-items:center; gap:14px;">
        <i class="fas fa-rocket" style="font-size:2rem;color:#00b7ff;"></i>
        <div>
            <h1>B-CONNECT Super Admin</h1>
            <p>Unified brand &amp; company management inside Believoo.</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <div class="bc-stat">
        <div class="bc-stat-icon" style="background:rgba(0,183,255,0.12);color:#00b7ff;"><i class="fas fa-building"></i></div>
        <div>
            <div style="font-size:1.8rem;font-weight:800;color:#f8fafc;">{{ $companies->total() }}</div>
            <div style="font-size:0.8rem;color:#64748b;">Companies</div>
        </div>
    </div>
    <div class="bc-stat">
        <div class="bc-stat-icon" style="background:rgba(34,197,94,0.12);color:#22c55e;"><i class="fas fa-rupee-sign"></i></div>
        <div>
            <div style="font-size:1.8rem;font-weight:800;color:#f8fafc;">₹{{ number_format($revenue, 2) }}</div>
            <div style="font-size:0.8rem;color:#64748b;">Total Revenue</div>
        </div>
    </div>
</div>

<div class="bc-card">
    <div style="padding:18px 20px;border-bottom:1px solid var(--border-color);">
        <h3 style="margin:0;font-size:1rem;font-weight:700;color:var(--text-primary);"><i class="fas fa-list" style="color:#00b7ff;margin-right:8px;"></i>Companies</h3>
    </div>
    <div style="overflow-x:auto;">
        <table class="bc-table">
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
                    <td><span class="bc-badge">{{ ucfirst($c->plan) }}</span></td>
                    <td>{{ $c->projects_count }}</td>
                    <td>{{ $c->members_count }}</td>
                    <td>{{ $c->tickets_count }}</td>
                    <td>{{ $c->invoices_count }}</td>
                    <td><a href="{{ route('admin.bconnect.company', $c->id) }}" class="bc-btn bc-btn-primary" style="padding:6px 12px;font-size:0.75rem;">View</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($companies->hasPages())
    <div style="padding: 16px 20px; border-top: 1px solid var(--border-color);">
        {{ $companies->links() }}
    </div>
    @endif
</div>
@endsection
