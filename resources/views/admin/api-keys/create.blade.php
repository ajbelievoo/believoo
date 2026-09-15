@extends('layouts.admin')

@section('title', 'New API Key')

@section('content')
<div class="container-fluid py-4">
    <h1 class="h3 mb-4">New API Key</h1>
    <div class="card shadow-sm">
        <div class="card-body">
            @include('admin.api-keys.form')
        </div>
    </div>
</div>
@endsection
