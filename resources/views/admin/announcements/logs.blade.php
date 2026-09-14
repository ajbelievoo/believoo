@extends('layouts.admin')

@section('title', 'Announcement Logs')

@section('content')
<div class="page-header">
    <h1 class="page-title">Logs: {{ $announcement->title }}</h1>
    <a href="{{ route('admin.announcements.index') }}" class="btn btn-secondary">Back</a>
</div>

<div class="data-table">
    <table>
        <thead>
            <tr><th>Time</th><th>Level</th><th>Email</th><th>Product</th><th>Message</th></tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
            <tr>
                <td>{{ $log->created_at }}</td>
                <td><span class="badge badge-{{ $log->level === 'error' ? 'danger' : ($log->level === 'warning' ? 'warning' : 'info') }}">{{ $log->level }}</span></td>
                <td>{{ $log->email ?? '—' }}</td>
                <td>{{ $log->product ?? '—' }}</td>
                <td>{{ $log->message }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center">No logs.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $logs->links() }}
</div>
@endsection
