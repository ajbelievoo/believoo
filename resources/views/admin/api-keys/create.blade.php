@extends('layouts.admin')

@section('title', 'New API Key')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">New API Key</h1>
        <p class="page-subtitle">Create a new REST API access token</p>
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
