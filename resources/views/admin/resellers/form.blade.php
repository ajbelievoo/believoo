@extends('layouts.admin')

@section('title', isset($reseller) ? 'Edit Reseller' : 'New Reseller')

@section('content')
<h1>{{ isset($reseller) ? 'Edit' : 'New' }} Reseller</h1>

<form method="POST" action="{{ isset($reseller) ? route('admin.resellers.update', $reseller) : route('admin.resellers.store') }}" class="row g-3 mt-3">
  @csrf
  @isset($reseller) @method('PUT') @endisset

  <div class="col-md-6">
    <label>Owner User</label>
    <select name="owner_user_id" class="form-control" required>
      @foreach($users as $id => $name)
      <option value="{{ $id }}" {{ (old('owner_user_id', $reseller->owner_user_id ?? '') == $id) ? 'selected' : '' }}>{{ $name }}</option>
      @endforeach
    </select>
  </div>

  <div class="col-md-6">
    <label>Company Name</label>
    <input type="text" name="company_name" class="form-control" value="{{ old('company_name', $reseller->company_name ?? '') }}" required>
  </div>

  <div class="col-md-6">
    <label>Slug</label>
    <input type="text" name="slug" class="form-control" value="{{ old('slug', $reseller->slug ?? '') }}" required>
  </div>

  <div class="col-md-6">
    <label>Custom Domain</label>
    <input type="text" name="custom_domain" class="form-control" value="{{ old('custom_domain', $reseller->custom_domain ?? '') }}">
  </div>

  <div class="col-md-3">
    <label>Brand Color</label>
    <input type="text" name="brand_color" class="form-control" value="{{ old('brand_color', $reseller->brand_color ?? '#00b7ff') }}">
  </div>

  <div class="col-md-3">
    <label>Default Margin %</label>
    <input type="number" step="0.0001" name="default_margin_percent" class="form-control" value="{{ old('default_margin_percent', $reseller->default_margin_percent ?? 10) }}">
  </div>

  <div class="col-md-3">
    <label>Support Email</label>
    <input type="email" name="support_email" class="form-control" value="{{ old('support_email', $reseller->support_email ?? '') }}">
  </div>

  <div class="col-md-3">
    <label>Billing Email</label>
    <input type="email" name="billing_email" class="form-control" value="{{ old('billing_email', $reseller->billing_email ?? '') }}">
  </div>

  <div class="col-md-6">
    <label>Logo URL</label>
    <input type="text" name="logo_url" class="form-control" value="{{ old('logo_url', $reseller->logo_url ?? '') }}">
  </div>

  <div class="col-md-6">
    <label>Favicon URL</label>
    <input type="text" name="favicon_url" class="form-control" value="{{ old('favicon_url', $reseller->favicon_url ?? '') }}">
  </div>

  <div class="col-12">
    <label>Settings JSON</label>
    <textarea name="settings" class="form-control" rows="5">{{ old('settings', isset($reseller) ? json_encode($reseller->settings, JSON_PRETTY_PRINT) : '{}') }}</textarea>
  </div>

  <div class="col-12">
    <div class="form-check">
      <input type="checkbox" name="is_active" class="form-check-input" value="1" {{ old('is_active', $reseller->is_active ?? true) ? 'checked' : '' }}>
      <label class="form-check-label">Active</label>
    </div>
  </div>

  <div class="col-12">
    <button class="btn btn-primary" type="submit">Save</button>
  </div>
</form>
@endsection
