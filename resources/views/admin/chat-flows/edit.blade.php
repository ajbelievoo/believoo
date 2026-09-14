@extends('layouts.admin')
@section('title', 'Edit Flow Step')
@section('content')
<div class="page-header"><h1 class="page-title">Edit Flow Step</h1></div>
<div class="data-table" style="max-width:800px;">
  <div style="padding:24px;">
    <form action="{{ route('admin.chat-flows.update', $chatFlow) }}" method="POST">@csrf @method('PUT')
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <div><label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:6px;">Flow Name</label><input type="text" name="name" value="{{ $chatFlow->name }}" required style="width:100%;padding:10px;background:var(--bg-tertiary);border:1px solid var(--border-color);border-radius:8px;color:var(--text-primary);"></div>
        <div><label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:6px;">Trigger Keywords</label><input type="text" name="trigger_keywords" value="{{ $chatFlow->trigger_keywords }}" required style="width:100%;padding:10px;background:var(--bg-tertiary);border:1px solid var(--border-color);border-radius:8px;color:var(--text-primary);"></div>
        <div><label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:6px;">Step Order</label><input type="number" name="step_order" value="{{ $chatFlow->step_order }}" style="width:100%;padding:10px;background:var(--bg-tertiary);border:1px solid var(--border-color);border-radius:8px;color:var(--text-primary);"></div>
        <div><label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:6px;">Step Type</label><select name="step_type" style="width:100%;padding:10px;background:var(--bg-tertiary);border:1px solid var(--border-color);border-radius:8px;color:var(--text-primary);"><option value="message" {{ $chatFlow->step_type == 'message' ? 'selected' : '' }}>Message</option><option value="ask_input" {{ $chatFlow->step_type == 'ask_input' ? 'selected' : '' }}>Ask Input</option><option value="action" {{ $chatFlow->step_type == 'action' ? 'selected' : '' }}>Action</option></select></div>
      </div>
      <div style="margin:16px 0;"><label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:6px;">Content</label><textarea name="content" rows="4" style="width:100%;padding:10px;background:var(--bg-tertiary);border:1px solid var(--border-color);border-radius:8px;color:var(--text-primary);">{{ $chatFlow->content }}</textarea></div>
      <div style="margin:16px 0;"><label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:6px;">Next Step (name)</label><input type="text" name="next_step" value="{{ $chatFlow->next_step }}" style="width:100%;padding:10px;background:var(--bg-tertiary);border:1px solid var(--border-color);border-radius:8px;color:var(--text-primary);"></div>
      <div style="margin-bottom:20px;"><label style="display:flex;align-items:center;gap:8px;"><input type="checkbox" name="is_active" value="1" {{ $chatFlow->is_active ? 'checked' : '' }}> Active</label></div>
      <button type="submit" class="btn btn-primary">Update</button>
      <a href="{{ route('admin.chat-flows.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
  </div>
</div>
@endsection
