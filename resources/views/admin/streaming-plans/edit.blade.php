@extends('layouts.admin')

@section('title', 'Edit Streaming Plan')

@section('content')
<div>
    <div class="page-header">
        <div><h1 class="page-title">Edit Streaming Plan</h1><p class="page-subtitle">{{ $streamingPlan->name }}</p></div>
        <a href="{{ route('admin.streaming-plans.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Plans
        </a>
    </div>

    <form action="{{ route('admin.streaming-plans.update', $streamingPlan) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row">
            <!-- Basic Information -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Plan Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-input" value="{{ old('name', $streamingPlan->name) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-input" rows="3">{{ old('description', $streamingPlan->description) }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Plan Slug <span class="text-danger">*</span></label>
                            <input type="text" name="slug" class="form-input" value="{{ old('slug', $streamingPlan->slug) }}" required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pricing -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-dollar-sign me-2"></i>Pricing</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Price (USD) <span class="text-danger">*</span></label>
                                <input type="number" name="price" class="form-input" step="0.01" min="0" value="{{ old('price', $streamingPlan->price) }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Setup Fee (USD)</label>
                                <input type="number" name="setup_fee" class="form-input" step="0.01" min="0" value="{{ old('setup_fee', $streamingPlan->setup_fee ?? 0) }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Billing Cycle</label>
                            <select name="billing_cycle" class="form-select">
                                <option value="monthly" {{ ($streamingPlan->billing_cycle ?? 'monthly') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                <option value="yearly" {{ ($streamingPlan->billing_cycle ?? '') == 'yearly' ? 'selected' : '' }}>Yearly</option>
                                <option value="one-time" {{ ($streamingPlan->billing_cycle ?? '') == 'one-time' ? 'selected' : '' }}>One Time</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Streaming Limits -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-broadcast-tower me-2"></i>Streaming Limits</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Max Viewers</label>
                                <input type="number" name="max_viewers" class="form-input" min="0" value="{{ old('max_viewers', $streamingPlan->max_viewers ?? 100) }}">
                                <small class="text-muted">0 = Unlimited</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Max Bitrate (kbps)</label>
                                <input type="number" name="max_bitrate" class="form-input" min="0" value="{{ old('max_bitrate', $streamingPlan->max_bitrate ?? 2000) }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Max Resolution</label>
                            <select name="max_resolution" class="form-select">
                                <option value="720p" {{ ($streamingPlan->max_resolution ?? '720p') == '720p' ? 'selected' : '' }}>720p HD</option>
                                <option value="1080p" {{ ($streamingPlan->max_resolution ?? '') == '1080p' ? 'selected' : '' }}>1080p Full HD</option>
                                <option value="4k" {{ ($streamingPlan->max_resolution ?? '') == '4k' ? 'selected' : '' }}>4K Ultra HD</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Bandwidth Limit (GB/month)</label>
                            <input type="number" name="bandwidth_gb" class="form-input" min="0" value="{{ old('bandwidth_gb', $streamingPlan->bandwidth_gb ?? 100) }}">
                            <small class="text-muted">0 = Unlimited</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Plan Settings -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Plan Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Delivery Method</label>
                            <select name="delivery_method" class="form-select">
                                <option value="vps_embedded" {{ ($streamingPlan->delivery_method ?? '') == 'vps_embedded' ? 'selected' : '' }}>VPS Embedded</option>
                                <option value="cloud_hosted" {{ ($streamingPlan->delivery_method ?? '') == 'cloud_hosted' ? 'selected' : '' }}>Cloud Hosted</option>
                                <option value="standalone" {{ ($streamingPlan->delivery_method ?? '') == 'standalone' ? 'selected' : '' }}>Standalone</option>
                            </select>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" name="is_addon" class="form-check-input" value="1" {{ $streamingPlan->is_addon ? 'checked' : '' }}>
                            <label class="form-check-label">Is Add-on Plan</label>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" name="is_active" class="form-check-input" value="1" {{ $streamingPlan->is_active ? 'checked' : '' }}>
                            <label class="form-check-label">Active</label>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" name="is_recommended" class="form-check-input" value="1" {{ $streamingPlan->is_recommended ? 'checked' : '' }}>
                            <label class="form-check-label">Recommended Plan</label>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Sort Order</label>
                            <input type="number" name="sort_order" class="form-input" min="0" value="{{ old('sort_order', $streamingPlan->sort_order ?? 0) }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Beauty Filter Settings -->
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card border-pink">
                    <div class="card-header text-white" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <h5 class="mb-0"><i class="fas fa-magic me-2"></i>AI Beauty Filter Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="form-check">
                                    <input type="checkbox" name="beauty_enabled" class="form-check-input" value="1" {{ ($streamingPlan->beauty_enabled ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label font-weight-bold">Enable Beauty Filters</label>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Skin Smoothing (0.0 - 1.0)</label>
                                <input type="number" name="beauty_skin_smoothing" class="form-input" step="0.01" min="0" max="1" value="{{ old('beauty_skin_smoothing', $streamingPlan->beauty_skin_smoothing ?? 0.50) }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Face Slimming (0.0 - 1.0)</label>
                                <input type="number" name="beauty_face_slimming" class="form-input" step="0.01" min="0" max="1" value="{{ old('beauty_face_slimming', $streamingPlan->beauty_face_slimming ?? 0.30) }}">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Eye Enlargement (0.0 - 1.0)</label>
                                <input type="number" name="beauty_eye_enlargement" class="form-input" step="0.01" min="0" max="1" value="{{ old('beauty_eye_enlargement', $streamingPlan->beauty_eye_enlargement ?? 0.20) }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Brightness (-1.0 to 1.0)</label>
                                <input type="number" name="beauty_brightness" class="form-input" step="0.01" min="-1" max="1" value="{{ old('beauty_brightness', $streamingPlan->beauty_brightness ?? 0.10) }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Beauty Preset</label>
                                <select name="beauty_preset" class="form-select">
                                    <option value="NATURAL" {{ ($streamingPlan->beauty_preset ?? 'NATURAL') == 'NATURAL' ? 'selected' : '' }}>Natural</option>
                                    <option value="GLAMOUR" {{ ($streamingPlan->beauty_preset ?? '') == 'GLAMOUR' ? 'selected' : '' }}>Glamour</option>
                                    <option value="PROFESSIONAL" {{ ($streamingPlan->beauty_preset ?? '') == 'PROFESSIONAL' ? 'selected' : '' }}>Professional</option>
                                    <option value="LIVE" {{ ($streamingPlan->beauty_preset ?? '') == 'LIVE' ? 'selected' : '' }}>Live Optimized</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-save me-2"></i>Update Plan
            </button>
            <a href="{{ route('admin.streaming-plans.index') }}" class="btn btn-secondary btn-lg">
                <i class="fas fa-times me-2"></i>Cancel
            </a>
        </div>
    </form>
</div>
@endsection
