@extends('layouts.admin')

@section('title', 'OVH Pricing Rules')

@section('content')
<div class="page-header">
  <div><h1 class="page-title">OVH Pricing Rules</h1><p class="page-subtitle">Manage catalog margins and markups</p></div>
  <a href="{{ route('admin.ovh-pricing-rules.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Add Rule</a>
</div>

<div class="card">
<div class="card-header"><div class="card-title"><i class="fas fa-sliders-h"></i>Pricing Rules</div></div>
<div class="card-body"><div class="table-responsive"><table class="data-table">
  <thead>
    <tr>
      <th>Category</th>
      <th>Plan Code</th>
      <th>Margin %</th>
      <th>Markup</th>
      <th>Min/Max %</th>
      <th>Priority</th>
      <th>Active</th>
      <th></th>
    </tr>
  </thead>
  <tbody>
    @foreach($rules as $rule)
    <tr>
      <td>{{ $rule->category }}</td>
      <td>{{ $rule->plan_code }}</td>
      <td>{{ $rule->margin_percent ?? '-' }}</td>
      <td>{{ $rule->fixed_markup }}</td>
      <td>{{ $rule->min_margin_percent ?? '-' }} / {{ $rule->max_margin_percent ?? '-' }}</td>
      <td>{{ $rule->priority }}</td>
      <td>{{ $rule->is_active ? 'Yes' : 'No' }}</td>
      <td>
        <a href="{{ route('admin.ovh-pricing-rules.edit', $rule) }}" class="btn btn-sm btn-secondary">Edit</a>
        <form action="{{ route('admin.ovh-pricing-rules.destroy', $rule) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this rule?')">
          @csrf @method('DELETE')
          <button class="btn btn-sm btn-danger">Delete</button>
        </form>
      </td>
    </tr>
    @endforeach
  </tbody>
</table></div></div>
<div class="card-footer">{{ $rules->links() }}</div>
</div>
@endsection
