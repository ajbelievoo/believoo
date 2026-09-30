@extends('layouts.admin')

@section('title', 'Conversation ' . Str::limit($sessionId, 12))

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Conversation <code style="color: var(--accent);">{{ Str::limit($sessionId, 24) }}</code></h1>
        <p class="page-subtitle">View full AI chat history</p>
    </div>
    <div class="page-actions">
        @if($user)
            <span class="badge badge-info">User: {{ $user->name }} ({{ $user->email }})</span>
        @endif
        <a href="{{ route('admin.ai-messages.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i>Back
        </a>
    </div>
</div>

@include('admin.partials.alerts')

<div class="card">
    <div class="card-body" style="max-height: 70vh; overflow-y: auto;">
        @foreach($messages as $message)
            <div class="d-flex mb-3 {{ $message->type === 'user' ? 'justify-content-end' : '' }}" style="display: flex; margin-bottom: 16px; {{ $message->type === 'user' ? 'justify-content: flex-end;' : '' }}">
                <div style="max-width: 75%; padding: 16px; border-radius: 14px; background: {{ $message->type === 'user' ? 'var(--accent)' : 'var(--bg-tertiary)' }}; border: 1px solid {{ $message->type === 'user' ? 'transparent' : 'var(--border-color)' }}; color: {{ $message->type === 'user' ? '#fff' : 'var(--text-primary)' }};">
                    <div style="font-size: 0.78rem; margin-bottom: 6px; color: {{ $message->type === 'user' ? 'rgba(255,255,255,0.7)' : 'var(--text-muted)' }};">
                        {{ $message->type === 'user' ? 'User' : 'AI' }} &bull; {{ $message->created_at->format('d M Y H:i') }}
                        @if($message->source)
                            &bull; {{ ucfirst($message->source) }}
                        @endif
                    </div>
                    <div style="font-size: 0.9rem; line-height: 1.5;">{!! nl2br(e($message->message)) !!}</div>
                    @if(!empty($message->citations))
                        <div style="margin-top: 10px; font-size: 0.78rem; color: {{ $message->type === 'user' ? 'rgba(255,255,255,0.7)' : 'var(--text-muted)' }};">
                            Citations:
                            @foreach($message->citations as $citation)
                                <span class="badge badge-slate">{{ $citation }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
