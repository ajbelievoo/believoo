@extends('layouts.admin')

@section('title', 'Knowledge Base')

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;">
    <div>
        <h1 class="page-title">AI Knowledge Base</h1>
        <p class="page-subtitle">Teach the AI — it will answer from these articles</p>
    </div>
    <a href="{{ route('admin.knowledge.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Add Article</a>
</div>

<div class="data-table">
    <div class="table-header"><h3 class="table-title">All Articles</h3></div>
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Category</th>
                <th>Keywords</th>
                <th>Used</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($articles as $a)
            <tr>
                <td style="font-weight:600;">{{ $a->title }}</td>
                <td>{{ $a->category ?? '—' }}</td>
                <td style="font-size:0.8rem;color:var(--text-muted);">{{ Str::limit($a->keywords, 40) }}</td>
                <td>{{ $a->used_count }}</td>
                <td><span class="badge badge-{{ $a->is_active ? 'success' : 'secondary' }}">{{ $a->is_active ? 'Active' : 'Off' }}</span></td>
                <td style="white-space:nowrap;">
                    <a href="{{ route('admin.knowledge.edit', $a) }}" class="btn btn-secondary" style="padding:6px 10px;font-size:0.75rem;"><i class="fas fa-edit"></i></a>
                    <form action="{{ route('admin.knowledge.destroy', $a) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-secondary" style="padding:6px 10px;font-size:0.75rem;color:#ef4444;"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-muted);">No articles yet. Add FAQs, policies, pricing info — AI will answer from them.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
