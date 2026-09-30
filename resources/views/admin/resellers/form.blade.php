@extends('layouts.admin')

@section('title', isset($reseller) ? 'Edit Reseller' : 'New Reseller')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ isset($reseller) ? 'Edit' : 'New' }} Reseller</h1>
        <p class="page-subtitle">Configure the white-label reseller details</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-store"></i>Reseller Details</div>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ isset($reseller) ? route('admin.resellers.update', $reseller) : route('admin.resellers.store') }}">
            @csrf
            @isset($reseller) @method('PUT') @endisset

            <div class="row">
                <div class="col-md-6 form-group">
                    <label class="form-label">Owner User</label>
                    <select name="owner_user_id" class="form-select" required>
                        @foreach($users as $id => $name)
                        <option value="{{ $id }}" {{ (old('owner_user_id', $reseller->owner_user_id ?? '') == $id) ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 form-group">
                    <label class="form-label">Company Name</label>
                    <input type="text" name="company_name" class="form-input" value="{{ old('company_name', $reseller->company_name ?? '') }}" required>
                </div>

                <div class="col-md-6 form-group">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" class="form-input" value="{{ old('slug', $reseller->slug ?? '') }}" required>
                </div>

                <div class="col-md-6 form-group">
                    <label class="form-label">Custom Domain</label>
                    <input type="text" name="custom_domain" class="form-input" value="{{ old('custom_domain', $reseller->custom_domain ?? '') }}">
                </div>

                <div class="col-md-3 form-group">
                    <label class="form-label">Brand Color</label>
                    <input type="text" name="brand_color" class="form-input" value="{{ old('brand_color', $reseller->brand_color ?? '#00b7ff') }}">
                </div>

                <div class="col-md-3 form-group">
                    <label class="form-label">Default Margin %</label>
                    <input type="number" step="0.0001" name="default_margin_percent" class="form-input" value="{{ old('default_margin_percent', $reseller->default_margin_percent ?? 10) }}">
                </div>

                <div class="col-md-3 form-group">
                    <label class="form-label">Support Email</label>
                    <input type="email" name="support_email" class="form-input" value="{{ old('support_email', $reseller->support_email ?? '') }}">
                </div>

                <div class="col-md-3 form-group">
                    <label class="form-label">Billing Email</label>
                    <input type="email" name="billing_email" class="form-input" value="{{ old('billing_email', $reseller->billing_email ?? '') }}">
                </div>

                <div class="col-md-6 form-group">
                    <label class="form-label">Logo URL</label>
                    <input type="text" name="logo_url" class="form-input" value="{{ old('logo_url', $reseller->logo_url ?? '') }}">
                </div>

                <div class="col-md-6 form-group">
                    <label class="form-label">Favicon URL</label>
                    <input type="text" name="favicon_url" class="form-input" value="{{ old('favicon_url', $reseller->favicon_url ?? '') }}">
                </div>

                <div class="col-12 form-group">
                    <label class="form-label">Settings JSON</label>
                    <textarea name="settings" class="form-textarea" rows="5">{{ old('settings', isset($reseller) ? json_encode($reseller->settings, JSON_PRETTY_PRINT) : '{}') }}</textarea>
                </div>

                <div class="col-12 form-group">
                    <label style="display: flex; align-items: center; gap: 8px; color: var(--text-secondary); font-size: 0.85rem; cursor: pointer;">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $reseller->is_active ?? true) ? 'checked' : '' }}>
                        Active
                    </label>
                </div>
            </div>

            <div class="card-footer" style="margin: 22px -22px -22px; border-radius: 0 0 var(--admin-radius) var(--admin-radius);">
                <button class="btn btn-primary" type="submit">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection
