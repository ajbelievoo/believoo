@extends('layouts.admin')

@section('title', 'AI Analytics')

@section('content')
<div class="page-header">
    <h1 class="page-title">AI Chat Analytics</h1>
    <p class="page-subtitle">How the AI assistant is performing</p>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px;">
    @php
        $cards = [
            ['Total Questions', $stats['total_chats'], 'fas fa-comments', '#f59e0b'],
            ['Today', $stats['today_chats'], 'fas fa-calendar-day', '#3b82f6'],
            ['This Week', $stats['week_chats'], 'fas fa-calendar-week', '#8b5cf6'],
            ['AI Replies', $stats['ai_replies'], 'fas fa-robot', '#a855f7'],
            ['👍 Positive', $stats['positive_feedback'], 'fas fa-thumbs-up', '#22c55e'],
            ['👎 Negative', $stats['negative_feedback'], 'fas fa-thumbs-down', '#ef4444'],
            ['Tickets Created', $stats['tickets_created'], 'fas fa-ticket-alt', '#06b6d4'],
            ['Human Handoffs', $stats['human_handoffs'], 'fas fa-user-headset', '#f97316'],
            ['KB Articles', $stats['kb_articles'], 'fas fa-brain', '#ec4899'],
            ['KB Hits', $stats['kb_used'], 'fas fa-book-open', '#10b981'],
        ];
    @endphp
    @foreach($cards as $c)
        <div class="data-table" style="padding:20px;text-align:center;">
            <i class="{{ $c[2] }}" style="font-size:1.4rem;color:{{ $c[3] }};margin-bottom:8px;display:block;"></i>
            <div style="font-size:1.6rem;font-weight:800;color:var(--text-primary);">{{ $c[1] }}</div>
            <div style="font-size:0.72rem;color:var(--text-muted);">{{ $c[0] }}</div>
        </div>
    @endforeach
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
    <div class="data-table">
        <div class="table-header"><h3 class="table-title">Top Asked Words</h3></div>
        <div style="padding:16px;display:flex;flex-wrap:wrap;gap:8px;">
            @forelse($topics as $word => $count)
                <span class="badge badge-warning" style="padding:4px 10px;font-size:0.75rem;">{{ $word }} ({{ $count }})</span>
            @empty
                <p style="color:var(--text-muted);">No data yet.</p>
            @endforelse
        </div>
    </div>
    <div class="data-table">
        <div class="table-header"><h3 class="table-title">Languages Used</h3></div>
        <div style="padding:16px;">
            @forelse($langs as $l)
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border-color);">
                    <span style="font-weight:600;">{{ strtoupper($l->lang ?? 'en') }}</span>
                    <span class="badge badge-info">{{ $l->cnt }}</span>
                </div>
            @empty
                <p style="color:var(--text-muted);">No data yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
