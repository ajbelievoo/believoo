@extends('layouts.admin')

@section('title', 'Create Streaming Plan')

@section('content')
<div>
    <div class="page-header">
        <div><h1 class="page-title">Create Streaming Plan</h1><p class="page-subtitle">Configure pricing, limits, delivery, and features</p></div>
        <a href="{{ route('admin.streaming-plans.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Plans
        </a>
    </div>

    <form action="{{ route('admin.streaming-plans.store') }}" method="POST">
        @csrf

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Plan Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-input" value="{{ old('name') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-input" rows="3" required>{{ old('description') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-dollar-sign me-2"></i>Pricing</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Price (USD) <span class="text-danger">*</span></label>
                                <input type="number" name="price" class="form-input" step="0.01" min="0" value="{{ old('price', 0) }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Add-on Price (USD)</label>
                                <input type="number" name="addon_price" class="form-input" step="0.01" min="0" value="{{ old('addon_price', 0) }}">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Billing Cycle <span class="text-danger">*</span></label>
                            <select name="billing_cycle" class="form-select" required>
                                <option value="monthly" selected>Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="half_yearly">Half Yearly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-broadcast-tower me-2"></i>Streaming Limits</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Max Viewers</label>
                                <input type="number" name="max_viewers" class="form-input" min="1" value="{{ old('max_viewers', 100) }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Max Bitrate (kbps)</label>
                                <input type="number" name="max_bitrate" class="form-input" min="100" value="{{ old('max_bitrate', 2000) }}">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Max Resolution</label>
                            <select name="max_resolution" class="form-select">
                                <option value="480">480p (SD)</option>
                                <option value="720" selected>720p (HD)</option>
                                <option value="1080">1080p (Full HD)</option>
                                <option value="1440">1440p (2K)</option>
                                <option value="2160">2160p (4K)</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Bandwidth (GB/month)</label>
                                <input type="number" name="bandwidth_gb" class="form-input" min="1" value="{{ old('bandwidth_gb', 100) }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Storage (GB)</label>
                                <input type="number" name="storage_gb" class="form-input" min="1" value="{{ old('storage_gb', 50) }}">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stream Count <span class="text-danger">*</span></label>
                                <input type="number" name="stream_count" class="form-input" min="1" max="50" value="{{ old('stream_count', 1) }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Max Projects <span class="text-danger">*</span></label>
                                <input type="number" name="max_projects" class="form-input" min="1" max="100" value="{{ old('max_projects', 5) }}" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Plan Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Delivery Method <span class="text-danger">*</span></label>
                            <select name="delivery_method" class="form-select" required>
                                <option value="vps_embedded" selected>VPS Embedded</option>
                                <option value="cloud_hosted">Cloud Hosted</option>
                            </select>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="is_addon" class="form-check-input" value="1" {{ old('is_addon') ? 'checked' : '' }}>
                            <label class="form-check-label">Is Add-on Plan</label>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="is_active" class="form-check-input" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label">Active</label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sort Order</label>
                            <input type="number" name="sort_order" class="form-input" min="0" value="{{ old('sort_order', 0) }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 mb-4">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Features</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-2 form-check"><input type="checkbox" name="rtmp_support" class="form-check-input" value="1" {{ old('rtmp_support') ? 'checked' : '' }}><label class="form-check-label">RTMP Support</label></div>
                            <div class="col-md-3 mb-2 form-check"><input type="checkbox" name="webrtc_support" class="form-check-input" value="1" {{ old('webrtc_support') ? 'checked' : '' }}><label class="form-check-label">WebRTC Support</label></div>
                            <div class="col-md-3 mb-2 form-check"><input type="checkbox" name="hls_support" class="form-check-input" value="1" {{ old('hls_support', true) ? 'checked' : '' }}><label class="form-check-label">HLS Support</label></div>
                            <div class="col-md-3 mb-2 form-check"><input type="checkbox" name="dash_support" class="form-check-input" value="1" {{ old('dash_support') ? 'checked' : '' }}><label class="form-check-label">DASH Support</label></div>
                            <div class="col-md-3 mb-2 form-check"><input type="checkbox" name="recording_enabled" class="form-check-input" value="1" {{ old('recording_enabled') ? 'checked' : '' }}><label class="form-check-label">Recording</label></div>
                            <div class="col-md-3 mb-2 form-check"><input type="checkbox" name="transcoding_enabled" class="form-check-input" value="1" {{ old('transcoding_enabled') ? 'checked' : '' }}><label class="form-check-label">Transcoding</label></div>
                            <div class="col-md-3 mb-2 form-check"><input type="checkbox" name="adaptive_bitrate" class="form-check-input" value="1" {{ old('adaptive_bitrate') ? 'checked' : '' }}><label class="form-check-label">Adaptive Bitrate</label></div>
                            <div class="col-md-3 mb-2 form-check"><input type="checkbox" name="low_latency" class="form-check-input" value="1" {{ old('low_latency') ? 'checked' : '' }}><label class="form-check-label">Low Latency</label></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-save me-2"></i>Create Plan
            </button>
            <a href="{{ route('admin.streaming-plans.index') }}" class="btn btn-secondary btn-lg">
                <i class="fas fa-times me-2"></i>Cancel
            </a>
        </div>
    </form>
</div>
@endsection
