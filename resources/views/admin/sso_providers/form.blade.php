@extends('layouts.admin')

@section('title', isset($provider) ? 'Edit SSO Provider' : 'New SSO Provider')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ isset($provider) ? 'Edit' : 'New' }} SSO Provider</h1>
        <p class="page-subtitle">Configure OIDC, SAML or OAuth provider settings</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-shield-alt"></i>Provider Details</div>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ isset($provider) ? route('admin.sso-providers.update', $provider) : route('admin.sso-providers.store') }}">
            @csrf
            @isset($provider) @method('PUT') @endisset

            <div class="row">
                <div class="col-md-6 form-group">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-input" value="{{ old('name', $provider->name ?? '') }}" required>
                </div>

                <div class="col-md-3 form-group">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select" required>
                        <option value="oidc" {{ (old('type', $provider->type ?? '') == 'oidc') ? 'selected' : '' }}>OIDC</option>
                        <option value="saml" {{ (old('type', $provider->type ?? '') == 'saml') ? 'selected' : '' }}>SAML</option>
                        <option value="oauth" {{ (old('type', $provider->type ?? '') == 'oauth') ? 'selected' : '' }}>OAuth</option>
                    </select>
                </div>

                <div class="col-md-3 form-group">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" class="form-input" value="{{ old('slug', $provider->slug ?? '') }}" required>
                </div>

                <div class="col-12 form-group">
                    <label class="form-label">Entity ID / Issuer</label>
                    <input type="text" name="entity_id" class="form-input" value="{{ old('entity_id', $provider->entity_id ?? '') }}">
                </div>

                <div class="col-12 form-group">
                    <label class="form-label">SAML Metadata XML (optional)</label>
                    <textarea name="metadata_xml" class="form-textarea" rows="4">{{ old('metadata_xml', $provider->metadata_xml ?? '') }}</textarea>
                </div>

                <div class="col-12 form-group">
                    <label class="form-label">Config JSON</label>
                    <textarea name="config" class="form-textarea" rows="8" required>{{ old('config', isset($provider) ? json_encode($provider->config, JSON_PRETTY_PRINT) : '{"client_id":"","client_secret":"","sso_url":"","slo_url":""}') }}</textarea>
                    <small style="color: var(--text-muted);">For SAML: idp.sso_url, idp.slo_url, idp.x509cert, sp.x509cert, sp.private_key</small>
                </div>

                <div class="col-md-3 form-group">
                    <label style="display: flex; align-items: center; gap: 8px; color: var(--text-secondary); font-size: 0.85rem; cursor: pointer;">
                        <input type="checkbox" name="auto_provision" value="1" {{ old('auto_provision', $provider->auto_provision ?? true) ? 'checked' : '' }}>
                        Auto Provision
                    </label>
                </div>

                <div class="col-md-3 form-group">
                    <label style="display: flex; align-items: center; gap: 8px; color: var(--text-secondary); font-size: 0.85rem; cursor: pointer;">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $provider->is_active ?? true) ? 'checked' : '' }}>
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
