@extends('layouts.admin')

@section('title', 'Streaming Plan - ' . $streamingPlan->name)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">{{ $streamingPlan->name }}</h1>
        <div>
            <a href="{{ route('admin.streaming-plans.edit', $streamingPlan) }}" class="btn btn-warning me-2">
                <i class="fas fa-edit me-2"></i>Edit
            </a>
            <a href="{{ route('admin.streaming-plans.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Plan Details</h5>
                </div>
                <div class="card-body">
                    <p><strong>Description:</strong> {{ $streamingPlan->description }}</p>
                    <div class="row">
                        <div class="col-md-4 mb-3"><strong>Price:</strong> ${{ number_format($streamingPlan->price, 2) }}/mo</div>
                        <div class="col-md-4 mb-3"><strong>Billing Cycle:</strong> {{ ucfirst($streamingPlan->billing_cycle) }}</div>
                        <div class="col-md-4 mb-3"><strong>Delivery:</strong> {{ str_replace('_', '-', $streamingPlan->delivery_method) }}</div>
                        <div class="col-md-4 mb-3"><strong>Viewers:</strong> {{ $streamingPlan->max_viewers >= 999999 ? 'Unlimited' : number_format($streamingPlan->max_viewers) }}</div>
                        <div class="col-md-4 mb-3"><strong>Bitrate:</strong> {{ $streamingPlan->getFormattedBitrate() }}</div>
                        <div class="col-md-4 mb-3"><strong>Resolution:</strong> {{ $streamingPlan->getFormattedResolution() }}</div>
                        <div class="col-md-4 mb-3"><strong>Bandwidth:</strong> {{ $streamingPlan->bandwidth_gb >= 999999 ? 'Unlimited' : $streamingPlan->bandwidth_gb . ' GB' }}</div>
                        <div class="col-md-4 mb-3"><strong>Storage:</strong> {{ $streamingPlan->storage_gb >= 999999 ? 'Unlimited' : $streamingPlan->storage_gb . ' GB' }}</div>
                        <div class="col-md-4 mb-3"><strong>Streams:</strong> {{ $streamingPlan->stream_count }}</div>
                    </div>
                    <div class="mb-3">
                        <strong>Status:</strong>
                        @if($streamingPlan->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </div>
                    @if($streamingPlan->is_addon)
                        <span class="badge bg-success">Add-on Plan</span>
                    @endif
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Features</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled row">
                        @foreach($streamingPlan->getFeaturesList() as $feature)
                            <li class="col-md-6 mb-2"><i class="fas fa-check text-success me-2"></i>{{ $feature }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Usage</h5>
                </div>
                <div class="card-body">
                    <p><strong>API Keys:</strong> {{ $streamingPlan->api_keys_count ?? 0 }}</p>
                    <p><strong>Subscriptions:</strong> {{ $streamingPlan->subscriptions_count ?? 0 }}</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.streaming-plans.toggle-status', $streamingPlan) }}" method="POST" class="mb-2">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-outline-info w-100">
                            <i class="fas fa-power-off me-2"></i>Toggle Status
                        </button>
                    </form>
                    <form action="{{ route('admin.streaming-plans.duplicate', $streamingPlan) }}" method="POST" class="mb-2">
                        @csrf
                        <button type="submit" class="btn btn-outline-success w-100">
                            <i class="fas fa-copy me-2"></i>Duplicate
                        </button>
                    </form>
                    <form action="{{ route('admin.streaming-plans.destroy', $streamingPlan) }}" method="POST" onsubmit="return confirm('Delete this plan?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="fas fa-trash me-2"></i>Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
