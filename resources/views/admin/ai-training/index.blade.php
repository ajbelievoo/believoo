@extends('layouts.admin')
@section('title', 'AI Training')
@section('content')
<div class="page-header"><h1 class="page-title">AI Training Center</h1><p>Fix wrong AI answers and improve responses</p></div>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
  <div class="data-table" style="padding:20px;text-align:center;"><h2 style="font-size:2rem;font-weight:800;">{{ $pending }}</h2><p>Pending Corrections</p></div>
  <div class="data-table" style="padding:20px;text-align:center;"><h2 style="font-size:2rem;font-weight:800;">{{ $applied }}</h2><p>Applied</p></div>
</div>
<div class="data-table">
  <div class="table-header"><h3 class="table-title">Corrections</h3></div>
  <table>
    <thead><tr><th>Question</th><th>Wrong Answer</th><th>Correct Answer</th><th>Applied</th><th>Actions</th></tr></thead>
    <tbody>
      @forelse($corrections as $c)
      <tr>
        <td>{{ $c->question }}</td>
        <td>{{ Str::limit($c->wrong_answer, 80) }}</td>
        <td>{{ Str::limit($c->correct_answer, 80) }}</td>
        <td>{!! $c->applied ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-warning">No</span>' !!}</td>
        <td style="white-space:nowrap;">
          @if(!$c->applied)
          <form action="{{ route('admin.ai-training.apply', $c) }}" method="POST" style="display:inline;">@csrf
            <button class="btn btn-primary" style="padding:4px 10px;font-size:0.7rem;">Apply to KB</button>
          </form>
          @endif
          <form action="{{ route('admin.ai-training.destroy', $c) }}" method="POST" style="display:inline;">@csrf @method('DELETE')
            <button class="btn btn-secondary" style="padding:4px 10px;font-size:0.7rem;color:#ef4444;"><i class="fas fa-trash"></i></button>
          </form>
        </td>
      </tr>
      @empty
      <tr><td colspan="5" style="text-align:center;padding:40px;color:var(--text-muted);">No corrections yet. Add them when AI gives wrong answers.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
