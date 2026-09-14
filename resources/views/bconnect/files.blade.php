@extends('bconnect.layout')
@section('title', 'File Manager')
@section('content')
<div class="flex flex-col h-[calc(100vh-140px)]">
    <div class="flex justify-between items-center mb-6">
        <h3 class="font-bold text-xl"><i class="fas fa-folder-open mr-2 text-cyan-400"></i>File Manager</h3>
        <form method="POST" action="{{ route('bconnect.files.store') }}" enctype="multipart/form-data" class="flex gap-2">@csrf
            <select name="project_id" class="bg-slate-800 border border-slate-600 rounded-lg text-sm p-2 text-white"><option value="">No Project</option>@foreach($projects as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select>
            <input type="file" name="file" required class="text-sm text-slate-400">
            <button type="submit" class="px-4 py-2 bg-cyan-500 text-slate-900 font-bold rounded-lg hover:bg-cyan-400">Upload</button>
        </form>
    </div>
    <div class="bg-slate-900 rounded-xl border border-slate-800 p-6 flex-1 overflow-auto">
        <table class="w-full text-sm">
            <thead><tr class="text-left text-slate-400 border-b border-slate-700"><th>Name</th><th>Project</th><th>Size</th><th>By</th><th>Action</th></tr></thead>
            <tbody>
                @forelse($files as $f)
                <tr class="border-b border-slate-800">
                    <td class="py-3"><a href="{{ Storage::url($f->path) }}" target="_blank" class="text-cyan-400 hover:underline">{{ $f->name }}</a></td>
                    <td>{{ $f->project?->name ?? '—' }}</td>
                    <td>{{ number_format($f->size / 1024, 2) }} KB</td>
                    <td>{{ $f->member->user->name }}</td>
                    <td><form method="POST" action="{{ route('bconnect.files.destroy', $f->id) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-red-400 text-xs"><i class="fas fa-trash"></i></button></form></td>
                </tr>
                @empty<tr><td colspan="5" class="py-6 text-center text-slate-500">No files uploaded.</td></tr>@endforelse
            </tbody>
        </table>
        {{ $files->links() }}
    </div>
</div>
@endsection
