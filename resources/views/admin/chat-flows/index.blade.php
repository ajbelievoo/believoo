@extends('layouts.admin')
@section('title', 'Chat Flows')
@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;"><h1 class="page-title">Chat Flow Builder</h1><a href="{{ route('admin.chat-flows.create') }}" class="btn btn-primary">+ Add Step</a></div>
<div class="data-table">
  <div class="table-header"><h3 class="table-title">Flow Steps</h3></div>
  <table>
    <thead><tr><th>Name</th><th>Trigger Keywords</th><th>Order</th><th>Type</th><th>Content</th><th>Active</th><th>Actions</th></tr></thead>
    <tbody>
      @forelse($flows as $f)
      <tr>
        <td style="font-weight:700;">{{ $f->name }}</td>
        <td>{{ $f->trigger_keywords }}</td>
        <td>{{ $f->step_order }}</td>
        <td>{{ $f->step_type }}</td>
        <td>{{ Str::limit($f->content, 60) }}</td>
        <td>{!! $f->is_active ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-secondary">No</span>' !!}</td>
        <td style="white-space:nowrap;">
          <a href="{{ route('admin.chat-flows.edit', $f) }}" class="btn btn-secondary" style="padding:4px 10px;font-size:0.7rem;"><i class="fas fa-edit"></i></a>
          <form action="{{ route('admin.chat-flows.destroy', $f) }}" method="POST" style="display:inline;">@csrf @method('DELETE')
            <button class="btn btn-secondary" style="padding:4px 10px;font-size:0.7rem;color:#ef4444;"><i class="fas fa-trash"></i></button>
          </form>
        </td>
      </tr>
      @empty
      <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted);">No flows yet. Example: trigger "refund" → ask for order id → show refund policy.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
