@extends('layouts.admin')

@section('title', 'New Email Campaign')

@section('content')
<div class="page-header">
    <h1 class="page-title">New Email Campaign</h1>
    <p class="page-subtitle">Compose a branded email blast — send now or schedule it</p>
</div>

@include('admin.partials.alerts')

@if($errors->any())
<div style="background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.35); color: #ef4444; padding: 12px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem;">
    @foreach($errors->all() as $error)
        <div><i class="fas fa-exclamation-circle"></i> {{ $error }}</div>
    @endforeach
</div>
@endif

<div class="data-table" style="max-width: 960px;">
    <div class="table-header"><h3 class="table-title">Campaign Details</h3></div>
    <div style="padding: 24px;">
        @include('admin.campaigns.form')
    </div>
</div>
@endsection
