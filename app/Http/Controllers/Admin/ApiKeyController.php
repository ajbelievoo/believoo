<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Http\Request;

class ApiKeyController extends Controller
{
    protected array $availableScopes = [
        'user:read',
        'servers:read',
        'servers:control',
        'server-imports:read',
        'server-imports:write',
        'licenses:read',
        'licenses:write',
        'tickets:read',
        'tickets:write',
        'currencies:read',
        'currencies:write',
        'streaming:read',
        'audio-mixer:read',
        'audio-mixer:write',
        'bconnect:read',
        'bconnect:write',
        '*',
    ];

    public function index(Request $request)
    {
        $query = ApiKey::with('user')->latest();

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($sq) use ($q) {
                $sq->where('name', 'like', "%{$q}%")
                   ->orWhere('key_prefix', 'like', "%{$q}%")
                   ->orWhereHas('user', function ($uq) use ($q) {
                       $uq->where('name', 'like', "%{$q}%")
                          ->orWhere('email', 'like', "%{$q}%");
                   });
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        $keys = $query->paginate(25)->withQueryString();

        return view('admin.api-keys.index', compact('keys'));
    }

    public function create()
    {
        $users = User::orderBy('name')->get(['id', 'name', 'email']);
        $scopes = $this->availableScopes;

        return view('admin.api-keys.create', compact('users', 'scopes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'scopes' => 'nullable|array',
            'scopes.*' => 'string|in:' . implode(',', $this->availableScopes),
            'allowed_ips' => 'nullable|string',
            'rate_limit' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $token = ApiKey::generateToken();

        $allowedIps = [];
        if (!empty($validated['allowed_ips'])) {
            $allowedIps = array_filter(array_map('trim', explode(',', $validated['allowed_ips'])));
        }

        $key = ApiKey::create([
            'user_id' => $validated['user_id'],
            'name' => $validated['name'],
            'key_hash' => $token['hash'],
            'key_prefix' => $token['prefix'],
            'scopes' => $validated['scopes'] ?? [],
            'allowed_ips' => $allowedIps,
            'rate_limit' => $validated['rate_limit'] ?? 60,
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()
            ->route('admin.api-keys.index')
            ->with('api_key_created', true)
            ->with('api_key_plain', $token['plain'])
            ->with('api_key_name', $key->name)
            ->with('success', 'API key created. Copy the token now — it will not be shown again.');
    }

    public function show(ApiKey $apiKey)
    {
        return view('admin.api-keys.show', compact('apiKey'));
    }

    public function edit(ApiKey $apiKey)
    {
        $users = User::orderBy('name')->get(['id', 'name', 'email']);
        $scopes = $this->availableScopes;

        return view('admin.api-keys.edit', compact('apiKey', 'users', 'scopes'));
    }

    public function update(Request $request, ApiKey $apiKey)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'scopes' => 'nullable|array',
            'scopes.*' => 'string|in:' . implode(',', $this->availableScopes),
            'allowed_ips' => 'nullable|string',
            'rate_limit' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $allowedIps = [];
        if (!empty($validated['allowed_ips'])) {
            $allowedIps = array_filter(array_map('trim', explode(',', $validated['allowed_ips'])));
        }

        $apiKey->update([
            'user_id' => $validated['user_id'],
            'name' => $validated['name'],
            'scopes' => $validated['scopes'] ?? [],
            'allowed_ips' => $allowedIps,
            'rate_limit' => $validated['rate_limit'] ?? 60,
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('admin.api-keys.index')->with('success', 'API key updated.');
    }

    public function destroy(ApiKey $apiKey)
    {
        $apiKey->delete();

        return redirect()->route('admin.api-keys.index')->with('success', 'API key revoked.');
    }

    public function regenerate(ApiKey $apiKey)
    {
        $token = ApiKey::generateToken();

        $apiKey->update([
            'key_hash' => $token['hash'],
            'key_prefix' => $token['prefix'],
        ]);

        return redirect()
            ->route('admin.api-keys.index')
            ->with('api_key_created', true)
            ->with('api_key_plain', $token['plain'])
            ->with('api_key_name', $apiKey->name)
            ->with('success', 'API key regenerated. Copy the new token now.');
    }
}
