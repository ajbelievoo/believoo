@extends('layouts.admin')

@section('title', 'SSO Providers')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1>SSO Providers</h1>
  <a href="{{ route('admin.sso-providers.create') }}" class="btn btn-primary">Add Provider</a>
</div>

<table class="table table-striped">
  <thead>
    <tr>
      <th>Name</th>
      <th>Type</th>
      <th>Slug</th>
      <th>Active</th>
      <th>Auto Provision</th>
      <th></th>
    </tr>
  </thead>
  <tbody>
    @foreach($providers as $provider)
    <tr>
      <td>{{ $provider->name }}</td>
      <td>{{ strtoupper($provider->type) }}</td>
      <td>{{ $provider->slug }}</td>
      <td>{{ $provider->is_active ? 'Yes' : 'No' }}</td>
      <td>{{ $provider->auto_provision ? 'Yes' : 'No' }}</td>
      <td>
        <a href="{{ route('sso.metadata', $provider->slug) }}" target="_blank" class="btn btn-sm btn-info">Metadata</a>
        <a href="{{ route('admin.sso-providers.edit', $provider) }}" class="btn btn-sm btn-secondary">Edit</a>
        <form action="{{ route('admin.sso-providers.destroy', $provider) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete provider?')">
          @csrf @method('DELETE')
          <button class="btn btn-sm btn-danger">Delete</button>
        </form>
      </td>
    </tr>
    @endforeach
  </tbody>
</table>

{{ $providers->links() }}
@endsection
