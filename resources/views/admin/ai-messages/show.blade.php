@extends('layouts.admin')

@section('title', 'Conversation ' . Str::limit($sessionId, 12))

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Conversation <code>{{ Str::limit($sessionId, 24) }}</code></h1>
        <div>
            @if($user)
                <span class="badge bg-info">User: {{ $user->name }} ({{ $user->email }})</span>
            @endif
            <a href="{{ route('admin.ai-messages.index') }}" class="btn btn-outline-secondary btn-sm ms-2">Back</a>
        </div>
    </div>

    @include('admin.partials.alerts')

    <div class="card shadow-sm">
        <div class="card-body" style="max-height: 70vh; overflow-y: auto;">
            @foreach($messages as $message)
                <div class="d-flex mb-3 {{ $message->type === 'user' ? 'justify-content-end' : '' }}">
                    <div class="p-3 rounded-3 {{ $message->type === 'user' ? 'bg-primary text-white' : 'bg-light border' }}" style="max-width: 75%;">
                        <div class="small mb-1 {{ $message->type === 'user' ? 'text-white-50' : 'text-muted' }}">
                            {{ $message->type === 'user' ? 'User' : 'AI' }} &bull; {{ $message->created_at->format('d M Y H:i') }}
                            @if($message->source)
                                &bull; {{ ucfirst($message->source) }}
                            @endif
                        </div>
                        <div>{!! nl2br(e($message->message)) !!}</div>
                        @if(!empty($message->citations))
                            <div class="mt-2 small {{ $message->type === 'user' ? 'text-white-50' : 'text-muted' }}">
                                Citations:
                                @foreach($message->citations as $citation)
                                    <span class="badge bg-secondary">{{ $citation }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
