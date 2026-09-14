@extends('layouts.admin')

@section('title', 'Announcement Templates')

@section('content')
<div class="page-header">
    <h1 class="page-title">Templates</h1>
    <p class="page-subtitle">Reusable announcement templates</p>
</div>

<div class="table-actions" style="margin-bottom: 20px;">
    <a href="{{ route('admin.announcement-templates.create') }}" class="btn btn-primary">New Template</a>
    <a href="{{ route('admin.announcements.index') }}" class="btn btn-secondary">Back to Announcements</a>
</div>

<div class="data-table">
    <div class="table-header"><h3 class="table-title">All Templates</h3></div>
    <table>
        <thead>
            <tr><th>Name</th><th>Title</th><th>Language</th><th>Actions</th></tr>
        </thead>
        <tbody>
            @forelse($templates as $template)
            <tr>
                <td>{{ $template->name }}</td>
                <td>{{ $template->title }}</td>
                <td>{{ strtoupper($template->locale) }}</td>
                <td>
                    <a href="{{ route('admin.announcement-templates.edit', $template) }}" class="btn btn-sm btn-secondary">Edit</a>
                    <form action="{{ route('admin.announcement-templates.destroy', $template) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="4" class="text-center">No templates.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $templates->links() }}
</div>
@endsection
