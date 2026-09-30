@extends('layouts.admin')

@section('title', 'Streaming Plans')

@section('content')
<div>
    <div>
        <div>
            <div class="page-header">
                <div><h1 class="page-title">Streaming Plans</h1><p class="page-subtitle">Manage streaming products and capacity</p></div>
                <a href="{{ route('admin.streaming-plans.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Create Plan
                </a>
            </div>

            <!-- Filter Tabs -->
            <div class="card mb-4">
                <div class="card-body">
                    <ul class="nav nav-pills">
                        <li class="nav-item">
                            <a class="nav-link active" href="#" data-bs-toggle="tab" data-bs-target="#all-plans">
                                All Plans <span class="badge bg-secondary ms-2">{{ $plans->count() }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-bs-toggle="tab" data-bs-target="#vps-embedded">
                                VPS-Embedded <span class="badge bg-purple ms-2">{{ $plans->where('delivery_method', 'vps_embedded')->count() }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-bs-toggle="tab" data-bs-target="#cloud-hosted">
                                Cloud-Hosted <span class="badge bg-blue ms-2">{{ $plans->where('delivery_method', 'cloud_hosted')->count() }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-bs-toggle="tab" data-bs-target="#addon-plans">
                                Add-ons <span class="badge bg-success ms-2">{{ $plans->where('is_addon', true)->count() }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-bs-toggle="tab" data-bs-target="#standalone-plans">
                                Standalone <span class="badge bg-info ms-2">{{ $plans->where('is_addon', false)->count() }}</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="tab-content">
                <!-- All Plans Tab -->
                <div class="tab-pane fade show active" id="all-plans">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Type</th>
                                            <th>Delivery Method</th>
                                            <th>Price</th>
                                            <th>Viewers</th>
                                            <th>Bandwidth</th>
                                            <th>Status</th>
                                            <th>Usage</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($plans as $plan)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div>
                                                        <div class="fw-bold">{{ $plan->name }}</div>
                                                        <small class="text-muted">{{ Str::limit($plan->description, 50) }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @if($plan->is_addon)
                                                    <span class="badge bg-success">Add-on</span>
                                                @else
                                                    <span class="badge bg-info">Standalone</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($plan->delivery_method === 'vps_embedded')
                                                    <span class="badge bg-purple">VPS-Embedded</span>
                                                @else
                                                    <span class="badge bg-blue">Cloud-Hosted</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($plan->is_addon)
                                                    <span class="fw-bold text-success">${{ number_format($plan->addon_price, 2) }}/mo</span>
                                                @else
                                                    <span class="fw-bold text-primary">${{ number_format($plan->price, 2) }}/mo</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($plan->max_viewers >= 999999)
                                                    <span class="badge bg-warning">Unlimited</span>
                                                @else
                                                    {{ number_format($plan->max_viewers) }}
                                                @endif
                                            </td>
                                            <td>
                                                @if($plan->bandwidth_gb >= 999999)
                                                    <span class="badge bg-warning">Unlimited</span>
                                                @else
                                                    {{ number_format($plan->bandwidth_gb) }}GB
                                                @endif
                                            </td>
                                            <td>
                                                @if($plan->is_active)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-secondary">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                <small>
                                                    <div>{{ $plan->api_keys_count }} API Keys</div>
                                                    <div>{{ $plan->subscriptions_count }} Subscriptions</div>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="{{ route('admin.streaming-plans.show', $plan) }}" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('admin.streaming-plans.edit', $plan) }}" class="btn btn-sm btn-outline-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('admin.streaming-plans.toggle-status', $plan) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="btn btn-sm btn-outline-info">
                                                            <i class="fas fa-power-off"></i>
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('admin.streaming-plans.duplicate', $plan) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-success">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('admin.streaming-plans.destroy', $plan) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="9" class="text-center">No streaming plans found.</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- VPS-Embedded Tab -->
                <div class="tab-pane fade" id="vps-embedded">
                    @include('admin.streaming-plans.partials.plan-table', ['plans' => $plans->where('delivery_method', 'vps_embedded'), 'title' => 'VPS-Embedded Plans'])
                </div>

                <!-- Cloud-Hosted Tab -->
                <div class="tab-pane fade" id="cloud-hosted">
                    @include('admin.streaming-plans.partials.plan-table', ['plans' => $plans->where('delivery_method', 'cloud_hosted'), 'title' => 'Cloud-Hosted Plans'])
                </div>

                <!-- Add-on Plans Tab -->
                <div class="tab-pane fade" id="addon-plans">
                    @include('admin.streaming-plans.partials.plan-table', ['plans' => $plans->where('is_addon', true), 'title' => 'Add-on Plans'])
                </div>

                <!-- Standalone Plans Tab -->
                <div class="tab-pane fade" id="standalone-plans">
                    @include('admin.streaming-plans.partials.plan-table', ['plans' => $plans->where('is_addon', false), 'title' => 'Standalone Plans'])
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
