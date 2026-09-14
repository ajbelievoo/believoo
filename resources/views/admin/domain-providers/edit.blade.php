@extends('layouts.admin')

@section('title', 'Edit Domain Provider: ' . $provider->name)

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Edit Provider: {{ $provider->name }}</h1>
        <p class="page-subtitle">Configure API credentials and settings</p>
    </div>
    <div>
        <a href="{{ route('admin.domain-providers.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left" style="margin-right: 8px;"></i>Back to Providers
        </a>
    </div>
</div>

@if(session('success'))
<div style="margin-bottom: 20px; padding: 16px; background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: 12px; color: #22c55e;">
    <i class="fas fa-check-circle" style="margin-right: 8px;"></i>{{ session('success') }}
</div>
@endif

@if(session('error'))
<div style="margin-bottom: 20px; padding: 16px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 12px; color: #ef4444;">
    <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>{{ session('error') }}
</div>
@endif

<form action="{{ route('admin.domain-providers.update', $provider) }}" method="POST">
    @csrf
    @method('PUT')

    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px;">
        
        {{-- Basic Settings --}}
        <div style="padding: 24px; background: linear-gradient(135deg, #1a1f2e 0%, #252b3d 100%); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px;">
            <h4 style="font-size: 1rem; font-weight: 600; color: #fff; margin-bottom: 20px;">
                <i class="fas fa-cog" style="margin-right: 8px; color: #00b7ff;"></i>Basic Settings
            </h4>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; color: #8b9bb4;">
                    Provider Name
                </label>
                <input type="text" name="name" value="{{ old('name', $provider->name) }}" required
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; color: var(--text-primary); font-size: 0.95rem;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; color: #8b9bb4;">
                    Provider Code
                </label>
                <input type="text" value="{{ $provider->code }}" disabled
                    style="width: 100%; background: rgba(0,0,0,0.3); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; color: #6b7280; font-size: 0.95rem;">
                <small style="color: #6b7280; font-size: 0.75rem;">This cannot be changed</small>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; color: #8b9bb4;">
                    Priority (Lower = Higher Priority)
                </label>
                <input type="number" name="priority" value="{{ old('priority', $provider->priority) }}" min="1" max="999" required
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; color: var(--text-primary); font-size: 0.95rem;">
                <small style="color: #6b7280; font-size: 0.75rem;">Used for dynamic routing selection</small>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $provider->is_active) ? 'checked' : '' }}
                        style="width: 20px; height: 20px; accent-color: #22c55e;">
                    <span style="font-size: 0.95rem; color: #fff;">Active Provider</span>
                </label>
                <small style="display: block; margin-top: 6px; color: #6b7280; font-size: 0.75rem;">
                    Only active providers are used for domain registration
                </small>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                    <input type="checkbox" name="test_mode" value="1" {{ old('test_mode', $provider->test_mode === '1') ? 'checked' : '' }}
                        style="width: 20px; height: 20px; accent-color: #eab308;">
                    <span style="font-size: 0.95rem; color: #fff;">Test Mode (Sandbox)</span>
                </label>
            </div>
        </div>

        {{-- API Configuration --}}
        <div style="padding: 24px; background: linear-gradient(135deg, #1a1f2e 0%, #252b3d 100%); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px;">
            <h4 style="font-size: 1rem; font-weight: 600; color: #fff; margin-bottom: 20px;">
                <i class="fas fa-key" style="margin-right: 8px; color: #8b5cf6;"></i>API Configuration
            </h4>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; color: #8b9bb4;">
                    API URL
                </label>
                <input type="url" name="api_url" value="{{ old('api_url', $provider->api_url) }}"
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; color: var(--text-primary); font-size: 0.95rem;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; color: #8b9bb4;">
                    {{ $provider->code === 'resellerclub' ? 'Auth UserID' : ($provider->code === 'cloudflare' ? 'Account ID' : 'Username') }}
                </label>
                <input type="text" name="username" value="{{ old('username', $provider->getMetadata($provider->code === 'resellerclub' ? 'auth_userid' : 'username', '')) }}"
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; color: var(--text-primary); font-size: 0.95rem;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; color: #8b9bb4;">
                    {{ $provider->code === 'cloudflare' ? 'API Token' : 'API Key' }}
                </label>
                <input type="password" name="api_key" placeholder="{{ $provider->getMetadata($provider->code === 'cloudflare' ? 'api_token' : 'api_key') ? '******** (set)' : 'Enter API key' }}"
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; color: var(--text-primary); font-size: 0.95rem;">
                @if($provider->getMetadata($provider->code === 'cloudflare' ? 'api_token' : 'api_key'))
                <small style="color: #6b7280; font-size: 0.75rem;">Leave blank to keep existing key</small>
                @endif
            </div>

            @if(in_array($provider->code, ['resellerclub', 'namecheap']))
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; color: #8b9bb4;">
                    Password (if required)
                </label>
                <input type="password" name="password" placeholder="{{ $provider->getMetadata('password') ? '******** (set)' : 'Enter password' }}"
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; color: var(--text-primary); font-size: 0.95rem;">
            </div>
            @endif

            <button type="button" onclick="testConnection()" class="btn btn-secondary" style="width: 100%; margin-top: 10px;">
                <i class="fas fa-plug" style="margin-right: 8px;"></i>Test Connection
            </button>
        </div>
    </div>

    {{-- Domain Settings --}}
    <div style="margin-top: 24px; padding: 24px; background: linear-gradient(135deg, #1a1f2e 0%, #252b3d 100%); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px;">
        <h4 style="font-size: 1rem; font-weight: 600; color: #fff; margin-bottom: 20px;">
            <i class="fas fa-globe" style="margin-right: 8px; color: #22c55e;"></i>Domain Settings
        </h4>

        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px;">
            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; color: #8b9bb4;">
                    Supported TLDs (comma-separated)
                </label>
                <textarea name="supported_tlds" rows="3"
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; color: var(--text-primary); font-size: 0.95rem; resize: vertical;">{{ old('supported_tlds', implode(', ', $provider->supported_tlds ?? [])) }}</textarea>
                <small style="color: #6b7280; font-size: 0.75rem;">Example: com, net, org, in, co.in</small>
            </div>

            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; color: #8b9bb4;">
                    Default Nameservers (comma-separated)
                </label>
                <textarea name="default_nameservers" rows="3"
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 12px 16px; color: var(--text-primary); font-size: 0.95rem; resize: vertical;">{{ old('default_nameservers', implode(', ', $provider->default_nameservers ?? [])) }}</textarea>
                <small style="color: #6b7280; font-size: 0.75rem;">Example: ns1.believoo.com, ns2.believoo.com</small>
            </div>
        </div>
    </div>

    <div style="margin-top: 24px; display: flex; gap: 12px;">
        <button type="submit" class="btn btn-primary" style="flex: 1;">
            <i class="fas fa-save" style="margin-right: 8px;"></i>Save Changes
        </button>
        <a href="{{ route('admin.domain-providers.index') }}" class="btn btn-secondary">
            Cancel
        </a>
    </div>
</form>

<script>
function testConnection() {
    const btn = event.target;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
    btn.disabled = true;
    
    fetch('{{ route('admin.domain-providers.test-connection', $provider) }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Connection successful!\\n\\n' + data.message);
        } else {
            alert('❌ Connection failed!\\n\\n' + data.message);
        }
    })
    .catch(error => {
        alert('❌ Test failed: ' + error.message);
    })
    .finally(() => {
        btn.innerHTML = originalHtml;
        btn.disabled = false;
    });
}
</script>
@endsection
