@php
    $isEdit = isset($apiKey);
    $action = $isEdit ? route('admin.api-keys.update', $apiKey) : route('admin.api-keys.store');
    $method = $isEdit ? 'patch' : 'post';
    $selectedScopes = old('scopes', ($apiKey ?? null)?->scopes ?? []);
    $allowedIps = old('allowed_ips', ($apiKey ?? null)?->allowed_ips ? implode(', ', $apiKey->allowed_ips) : '');
@endphp

<form method="post" action="{{ $action }}">
    @csrf
    @method($method)

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Owner</label>
            <select name="user_id" class="form-select" required>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ old('user_id', ($apiKey ?? null)?->user_id ?? auth()->id()) == $user->id ? 'selected' : '' }}>
                        {{ $user->name }} ({{ $user->email }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Key Name</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', ($apiKey ?? null)?->name ?? '') }}" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Rate Limit (requests / minute)</label>
            <input type="number" name="rate_limit" class="form-control" value="{{ old('rate_limit', ($apiKey ?? null)?->rate_limit ?? 60) }}" min="1" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Expires At</label>
            <input type="datetime-local" name="expires_at" class="form-control" value="{{ old('expires_at', ($apiKey ?? null)?->expires_at ? $apiKey->expires_at->format('Y-m-d\TH:i') : '') }}">
            <div class="form-text">Leave blank for no expiry.</div>
        </div>
        <div class="col-12">
            <label class="form-label">Allowed IPs / CIDRs</label>
            <input type="text" name="allowed_ips" class="form-control" value="{{ $allowedIps }}" placeholder="192.168.1.1, 10.0.0.0/8">
            <div class="form-text">Comma-separated. Leave blank to allow all IPs.</div>
        </div>
        <div class="col-12">
            <label class="form-label">Scopes</label>
            <div class="row g-2">
                @foreach($scopes as $scope)
                    <div class="col-md-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="scopes[]" value="{{ $scope }}" id="scope_{{ Str::slug($scope) }}" {{ in_array($scope, $selectedScopes) ? 'checked' : '' }}>
                            <label class="form-check-label" for="scope_{{ Str::slug($scope) }}">{{ $scope }}</label>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="col-12">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', ($apiKey ?? null)?->is_active ?? true) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">Active</label>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Update Key' : 'Create Key' }}</button>
        <a href="{{ route('admin.api-keys.index') }}" class="btn btn-link">Cancel</a>
    </div>
</form>
