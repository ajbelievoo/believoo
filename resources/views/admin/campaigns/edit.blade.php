@extends('layouts.admin')

@section('title', 'Edit Email Campaign')

@section('content')
<div class="container-fluid py-4">
    <h1 class="h3 mb-4">Edit Email Campaign</h1>
    <div class="card shadow-sm">
        <div class="card-body">
            @include('admin.campaigns.form')
        </div>
    </div>
</div>
@endsection
