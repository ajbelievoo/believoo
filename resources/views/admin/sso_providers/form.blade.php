@extends('layouts.admin')

@section('title', isset($provider) ? 'Edit SSO Provider' : 'New SSO Provider')

@section('content')
<h1>{{ isset($provider) ? 'Edit' : 'New' }} SSO Provider</h1>

<form method="POST" action="{{ isset($provider) ? route('admin.sso-providers.update', $provider) : route('admin.sso-providers.store') }}" class="row g-3 mt-3">
  @csrf
  @isset($provider) @method('PUT') @endisset

  <div class="col-md-6">
    <label>Name</label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $provider->name ?? '') }}" required>
  </div>

  <div class="col-md-3">
    <label>Type</label>
    <select name="type" class="form-control" required>
      <option value="oidc" {{ (old('type', $provider->type ?? '') == 'oidc') ? 'selected' : '' }}>OIDC</option>
      <option value="saml" {{ (old('type', $provider->type ?? '') == 'saml') ? 'selected' : '' }}>SAML</option>
      <option value="oauth" {{ (old('type', $provider->type ?? '') == 'oauth') ? 'selected' : '' }}>OAuth</option>
    </select>
  </div>

  <div class="col-md-3">
    <label>Slug</label>
    <input type="text" name="slug" class="form-control" value="{{ old('slug', $provider->slug ?? '') }}" required>
  </div>

  <div class="col-12">
    <label>Entity ID / Issuer</label>
    <input type="text" name="entity_id" class="form-control" value="{{ old('entity_id', $provider->entity_id ?? '') }}">
  </div>

  <div class="col-12">
    <label>SAML Metadata XML (optional)</label>
    <textarea name="metadata_xml" class="form-control" rows="4">{{ old('metadata_xml', $provider->metadata_xml ?? '') }}</textarea>
  </div>

  <div class="col-12">
    <label>Config JSON</label>
    <textarea name="config" class="form-control" rows="8" required>{{ old('config', isset($provider) ? json_encode($provider->config, JSON_PRETTY_PRINT) : '{"client_id":"","client_secret":"","sso_url":"","slo_url":""}') }}</textarea>
    <small class="form-text text-muted">For SAML: idp.sso_url, idp.slo_url, idp.x509cert, sp.x509cert, sp.private_key</small>
  </div>

  <div class="col-md-3">
    <div class="form-check">
      <input type="checkbox" name="auto_provision" class="form-check-input" value="1" {{ old('auto_provision', $provider->auto_provision ?? true) ? 'checked' : '' }}>
      <label class="form-check-label">Auto Provision</label>
    </div>
  </div>

  <div class="col-md-3">
    <div class="form-check">
      <input type="checkbox" name="is_active" class="form-check-input" value="1" {{ old('is_active', $provider->is_active ?? true) ? 'checked' : '' }}>
      <label class="form-check-label">Active</label>
    </div>
  </div>

  <div class="col-12">
    <button class="btn btn-primary" type="submit">Save</button>
  </div>
</form>
@endsection
