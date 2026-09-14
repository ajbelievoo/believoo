@extends('layouts.admin')

@section('title', 'Projects')

@section('content')
<div class="page-header">
    <h1 class="page-title">Projects</h1>
    <p class="page-subtitle">Manage all client projects</p>
</div>

<div class="data-table">
    <div class="table-header">
        <h3 class="table-title">All Projects</h3>
    </div>
    <table>
        <thead>
            <tr>
                <th>Project</th>
                <th>Client</th>
                <th>Progress</th>
                <th>Status</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($projects as $project)
            <tr>
                <td style="font-weight: 600;">{{ $project->name }}</td>
                <td>{{ $project->user->name ?? '—' }}</td>
                <td>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="flex: 1; height: 6px; background: var(--bg-tertiary); border-radius: 3px; overflow: hidden;">
                            <div style="height: 100%; width: {{ $project->progress ?? 0 }}%; background: linear-gradient(90deg, #00b7ff, #0099ff); border-radius: 3px;"></div>
                        </div>
                        <span style="font-size: 0.8rem; color: var(--text-muted);">{{ $project->progress ?? 0 }}%</span>
                    </div>
                </td>
                <td>
                    <span class="badge badge-{{ $project->status === 'completed' ? 'success' : ($project->status === 'in_progress' ? 'info' : ($project->status === 'on_hold' ? 'warning' : ($project->status === 'cancelled' ? 'danger' : 'warning'))) }}">
                        {{ ucwords(str_replace('_', ' ', $project->status)) }}
                    </span>
                </td>
                <td>{{ $project->start_date ? \Carbon\Carbon::parse($project->start_date)->format('M d, Y') : '—' }}</td>
                <td>{{ $project->end_date ? \Carbon\Carbon::parse($project->end_date)->format('M d, Y') : '—' }}</td>
                <td>
                    <a href="{{ route('admin.projects.show', $project) }}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem;">
                        <i class="fas fa-eye"></i>
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 60px; color: var(--text-muted);">
                    <i class="fas fa-project-diagram" style="font-size: 3rem; margin-bottom: 16px; opacity: 0.3;"></i>
                    <p>No projects found</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($projects->hasPages())
    <div style="padding: 20px 24px; border-top: 1px solid var(--border-color);">
        {{ $projects->links() }}
    </div>
    @endif
</div>
@endsection
