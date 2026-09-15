@extends('layouts.admin')

@section('title', 'Resellers')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1>Resellers / White-Label Partners</h1>
  <a href="{{ route('admin.resellers.create') }}" class="btn btn-primary">Add Reseller</a>
</div>

<table class="table table-striped">
  <thead>
    <tr>
      <th>Company</th>
      <th>Owner</th>
      <th>Slug</th>
      <th>Custom Domain</th>
      <th>Margin %</th>
      <th>Approved</th>
      <th></th>
    </tr>
  </thead>
  <tbody>
    @foreach($resellers as $reseller)
    <tr>
      <td>{{ $reseller->company_name }}</td>
      <td>{{ $reseller->owner?->name }}</td>
      <td>{{ $reseller->slug }}</td>
      <td>{{ $reseller->custom_domain ?? '-' }}</td>
      <td>{{ $reseller->default_margin_percent }}</td>
      <td>{{ $reseller->approved_at ? $reseller->approved_at->format('Y-m-d') : 'Pending' }}</td>
      <td>
        @if(!$reseller->approved_at)
        <form action="{{ route('admin.resellers.approve', $reseller) }}" method="POST" class="d-inline">
          @csrf @method('PATCH')
          <button class="btn btn-sm btn-success">Approve</button>
        </form>
        @endif
        <a href="{{ route('admin.resellers.edit', $reseller) }}" class="btn btn-sm btn-secondary">Edit</a>
        <form action="{{ route('admin.resellers.destroy', $reseller) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete reseller?')">
          @csrf @method('DELETE')
          <button class="btn btn-sm btn-danger">Delete</button>
        </form>
      </td>
    </tr>
    @endforeach
  </tbody>
</table>

{{ $resellers->links() }}
@endsection
