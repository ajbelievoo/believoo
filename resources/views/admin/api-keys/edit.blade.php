@extends('layouts.admin')

@section('title', 'Edit API Key')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Edit API Key</h1>
        <p class="page-subtitle">Update API key settings and permissions</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-key"></i>Key Details</div>
    </div>
    <div class="card-body">
        @include('admin.api-keys.form')
    </div>
</div>
@endsection
