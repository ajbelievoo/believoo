@extends('layouts.admin')
@section('title', 'Brand Hub')
@section('content')
<style>
    .brand-hero {
        background: linear-gradient(135deg, #0b1220 0%, #111827 40%, #0b1220 100%);
        border: 1px solid rgba(0,183,255,0.15);
        border-radius: 18px;
        padding: 28px;
        margin-bottom: 24px;
        position: relative;
        overflow: hidden;
    }
    .brand-hero::before {
        content: "";
        position: absolute;
        top: -50%;
        right: -10%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(0,183,255,0.15) 0%, transparent 70%);
        border-radius: 50%;
    }
    .brand-hero h1 { font-size: 1.8rem; font-weight: 700; color: #f8fafc; margin: 0; }
    .brand-hero p { color: #94a3b8; margin: 6px 0 0; font-size: 0.9rem; }
    .brand-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; }
    .brand-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 24px;
        position: relative;
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
        box-shadow: 0 4px 20px rgba(0,0,0,0.04);
    }
    .brand-card:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(0,0,0,0.08); border-color: rgba(255,255,255,0.12); }
    .brand-card::after {
        content: "";
        position: absolute;
        top: 0; left: 0; width: 4px; height: 100%;
    }
    .brand-icon {
        width: 58px; height: 58px; border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.6rem;
        margin-bottom: 16px;
    }
    .brand-title { font-size: 1.15rem; font-weight: 700; color: var(--text-primary); margin: 0 0 6px; }
    .brand-tagline { font-size: 0.85rem; color: #94a3b8; margin-bottom: 16px; line-height: 1.4; }
    .brand-stats { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 20px; }
    .brand-stat { text-align: center; }
    .brand-stat-value { font-size: 1.2rem; font-weight: 700; color: #f8fafc; }
    .brand-stat-label { font-size: 0.7rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; }
    .brand-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 600;
        color: white;
        text-decoration: none;
        transition: opacity 0.2s;
    }
    .brand-btn:hover { opacity: 0.92; text-decoration: none; color: white; }
    .brand-btn-disabled {
        background: rgba(148,163,184,0.2);
        color: #94a3b8;
        cursor: not-allowed;
    }
    .brand-btn-disabled:hover { opacity: 1; color: #94a3b8; }
</style>

<div class="brand-hero">
    <div style="display:flex;align-items:center;gap:14px;">
        <i class="fas fa-layer-group" style="font-size:2rem;color:#00b7ff;"></i>
        <div>
            <h1>Brand Hub</h1>
            <p>All Believoo brands managed from one place.</p>
        </div>
    </div>
</div>

<div class="brand-grid">
    @foreach($brands as $b)
    <div class="brand-card" style="border-top: 3px solid {{ $b['color'] }};">
        <div class="brand-icon" style="background:{{ $b['color'] }}15;color:{{ $b['color'] }};">
            <i class="fas {{ $b['icon'] }}"></i>
        </div>
        <div class="brand-title">{{ $b['name'] }}</div>
        <div class="brand-tagline">{{ $b['tagline'] }}</div>
        <div class="brand-stats">
            @foreach($b['stats'] as $label => $value)
            <div class="brand-stat">
                <div class="brand-stat-value">{{ is_numeric($value) ? number_format($value) : $value }}</div>
                <div class="brand-stat-label">{{ $label }}</div>
            </div>
            @endforeach
        </div>
        @if(in_array($b['key'], ['iyolme','hitune']))
        <span class="brand-btn brand-btn-disabled"><i class="fas fa-clock"></i> Coming Soon</span>
        @else
        <a href="{{ $b['link'] }}" class="brand-btn" style="background:{{ $b['color'] }};"><i class="fas fa-arrow-right"></i> Manage</a>
        @endif
    </div>
    @endforeach
</div>
@endsection
