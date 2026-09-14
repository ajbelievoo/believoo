@extends('layouts.admin')

@section('title', 'Blog Posts')

@section('content')
<div class="page-header">
    <h1 class="page-title">Blog Posts</h1>
    <p class="page-subtitle">Manage SEO articles and news</p>
</div>

<div class="data-table">
    <div class="table-header">
        <h3 class="table-title">All Posts</h3>
        <div class="table-actions">
            <a href="{{ route('admin.posts.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Post
            </a>
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Slug</th>
                <th>Status</th>
                <th>Published</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($posts as $post)
            <tr>
                <td>
                    <div style="font-weight: 600;">{{ $post->title }}</div>
                </td>
                <td>{{ $post->slug }}</td>
                <td>
                    <span class="badge badge-{{ $post->is_published ? 'success' : 'danger' }}">
                        {{ $post->is_published ? 'Published' : 'Draft' }}
                    </span>
                </td>
                <td>{{ $post->published_at?->format('d M Y') ?? '—' }}</td>
                <td>
                    <div style="display: flex; gap: 8px;">
                        <a href="{{ route('admin.posts.edit', $post) }}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem;">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('admin.posts.destroy', $post) }}" method="POST" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem; background: rgba(239,68,68,0.1); color: #ef4444; border-color: rgba(239,68,68,0.3);" onclick="return confirm('Delete this post?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center; padding: 40px;">No posts found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <div style="padding: 16px;">
        {{ $posts->links() }}
    </div>
</div>
@endsection
