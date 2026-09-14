@extends('layouts.admin')

@section('title', 'Project - ' . $project->name)

@section('content')
<div class="page-header">
    <h1 class="page-title">{{ $project->name }}</h1>
    <p class="page-subtitle">{{ $project->user->name ?? '—' }}</p>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
    <div>
        <div class="data-table" style="margin-bottom: 24px;">
            <div class="table-header"><h3 class="table-title">Project Details</h3></div>
            <div style="padding: 24px;">
                <p style="color: var(--text-secondary); margin-bottom: 20px;">{{ $project->description ?? 'No description provided.' }}</p>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Client</div>
                        <div style="font-weight: 600;">{{ $project->user->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Progress</div>
                        <div style="font-weight: 600;">{{ $project->progress ?? 0 }}%</div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Start Date</div>
                        <div>{{ $project->start_date ? \Carbon\Carbon::parse($project->start_date)->format('M d, Y') : '—' }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">End Date</div>
                        <div>{{ $project->end_date ? \Carbon\Carbon::parse($project->end_date)->format('M d, Y') : '—' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="data-table">
            <div class="table-header"><h3 class="table-title">Assets</h3></div>
            <table>
                <thead>
                    <tr><th>Name</th><th>Type</th><th>Date</th></tr>
                </thead>
                <tbody>
                    @forelse($project->assets as $asset)
                    <tr>
                        <td>{{ $asset->name }}</td>
                        <td>{{ $asset->type ?? '—' }}</td>
                        <td>{{ $asset->created_at->format('M d, Y') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" style="text-align:center; padding: 30px; color: var(--text-muted);">No assets</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <div class="data-table">
            <div class="table-header"><h3 class="table-title">Update Status</h3></div>
            <div style="padding: 24px;">
                <form action="{{ route('admin.projects.update-status', $project) }}" method="POST">
                    @csrf
                    <select name="status" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); margin-bottom: 12px;">
                        @foreach(['pending','in_progress','completed','on_hold','cancelled'] as $s)
                        <option value="{{ $s }}" {{ $project->status === $s ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $s)) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Update Status</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
