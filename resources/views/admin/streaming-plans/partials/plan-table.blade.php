<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">{{ $title }}</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
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
                        <td colspan="8" class="text-center">No {{ strtolower($title) }} found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
