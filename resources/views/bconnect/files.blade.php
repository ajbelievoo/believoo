@extends('bconnect.layout')
@section('title', 'File Manager')
@section('content')
<div class="flex flex-col h-[calc(100vh-140px)]">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-6">
        <h3 class="font-bold text-xl"><i class="fas fa-folder-open mr-2 text-cyan-400"></i>File Manager</h3>
        <form method="POST" action="{{ route('bconnect.files.store') }}" enctype="multipart/form-data" class="flex flex-wrap gap-2">@csrf
            <select name="project_id" class="bc-input text-sm py-2 w-auto"><option value="">No Project</option>@foreach($projects as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select>
            <input type="file" name="file" required class="text-sm text-slate-400 self-center">
            <button type="submit" class="bc-btn bc-btn-primary text-sm"><i class="fas fa-upload mr-1"></i>Upload</button>
        </form>
    </div>
    <div class="bc-card flex-1 overflow-auto p-4 md:p-6">
        <div class="overflow-x-auto">
            <table class="bc-table min-w-[600px]">
                <thead><tr><th>Name</th><th>Project</th><th>Size</th><th>By</th><th>Action</th></tr></thead>
                <tbody>
                    @forelse($files as $f)
                    <tr>
                        <td><a href="{{ Storage::url($f->path) }}" target="_blank" class="text-cyan-400 hover:underline font-medium">{{ $f->name }}</a></td>
                        <td class="text-slate-400">{{ $f->project?->name ?? '—' }}</td>
                        <td class="text-slate-400">{{ number_format($f->size / 1024, 2) }} KB</td>
                        <td class="text-slate-400">{{ $f->member->user->name }}</td>
                        <td><form method="POST" action="{{ route('bconnect.files.destroy', $f->id) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-red-400 hover:text-red-300 text-xs"><i class="fas fa-trash"></i></button></form></td>
                    </tr>
                    @empty<tr><td colspan="5" class="bc-empty">No files uploaded.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
        {{ $files->links() }}
    </div>
</div>
@endsection
