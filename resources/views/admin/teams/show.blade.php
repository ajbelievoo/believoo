@extends('layouts.admin')

@section('title', $team->name)

@section('content')
<div class="page-header">
    <h1 class="page-title">{{ $team->name }}</h1>
    <p class="page-subtitle">{{ $team->role }}</p>
</div>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px; max-width: 900px;">
    <div class="data-table" style="padding: 24px; text-align: center;">
        <img src="{{ $team->photo_url }}" alt="{{ $team->name }}" loading="lazy" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin-bottom: 16px;">
        <h3 style="font-weight: 700; margin-bottom: 4px;">{{ $team->name }}</h3>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 16px;">{{ $team->role }}</p>
        <span class="badge badge-{{ $team->is_active ? 'success' : 'danger' }}">{{ $team->is_active ? 'Active' : 'Inactive' }}</span>
    </div>

    <div class="data-table">
        <div class="table-header"><h3 class="table-title">Details</h3></div>
        <div style="padding: 24px; display: flex; flex-direction: column; gap: 16px;">
            @if($team->bio)
            <div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Bio</div>
                <p style="color: var(--text-secondary);">{{ $team->bio }}</p>
            </div>
            @endif
            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                @if($team->linkedin) <a href="{{ $team->linkedin }}" target="_blank" class="btn btn-secondary" style="padding: 8px 14px;"><i class="fab fa-linkedin"></i></a> @endif
                @if($team->twitter) <a href="{{ $team->twitter }}" target="_blank" class="btn btn-secondary" style="padding: 8px 14px;"><i class="fab fa-twitter"></i></a> @endif
                @if($team->facebook) <a href="{{ $team->facebook }}" target="_blank" class="btn btn-secondary" style="padding: 8px 14px;"><i class="fab fa-facebook"></i></a> @endif
                @if($team->instagram) <a href="{{ $team->instagram }}" target="_blank" class="btn btn-secondary" style="padding: 8px 14px;"><i class="fab fa-instagram"></i></a> @endif
            </div>
            <div style="display: flex; gap: 12px; margin-top: 8px;">
                <a href="{{ route('admin.teams.edit', $team) }}" class="btn btn-primary"><i class="fas fa-edit"></i> Edit</a>
                <a href="{{ route('admin.teams.index') }}" class="btn btn-secondary">Back</a>
            </div>
        </div>
    </div>
</div>
@endsection
