<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SsoProvider;
use Illuminate\Http\Request;

class SsoProviderController extends Controller
{
    public function index()
    {
        $providers = SsoProvider::orderBy('name')->paginate(25);
        return view('admin.sso_providers.index', compact('providers'));
    }

    public function create()
    {
        return view('admin.sso_providers.form');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:oidc,saml,oauth',
            'slug' => 'required|string|max:255|unique:sso_providers,slug',
            'entity_id' => 'nullable|string|max:1000',
            'metadata_xml' => 'nullable|string',
            'config' => 'nullable|json',
            'auto_provision' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $data['config'] = $data['config'] ? json_decode($data['config'], true) : [];
        $data['is_active'] = $request->boolean('is_active', true);
        $data['auto_provision'] = $request->boolean('auto_provision', true);

        SsoProvider::create($data);

        return redirect()->route('admin.sso-providers.index')->with('success', 'SSO provider created.');
    }

    public function edit(SsoProvider $ssoProvider)
    {
        return view('admin.sso_providers.form', compact('ssoProvider'));
    }

    public function update(Request $request, SsoProvider $ssoProvider)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:oidc,saml,oauth',
            'slug' => 'required|string|max:255|unique:sso_providers,slug,' . $ssoProvider->id,
            'entity_id' => 'nullable|string|max:1000',
            'metadata_xml' => 'nullable|string',
            'config' => 'nullable|json',
            'auto_provision' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $data['config'] = $data['config'] ? json_decode($data['config'], true) : [];
        $data['is_active'] = $request->boolean('is_active', true);
        $data['auto_provision'] = $request->boolean('auto_provision', true);

        $ssoProvider->update($data);

        return redirect()->route('admin.sso-providers.index')->with('success', 'SSO provider updated.');
    }

    public function destroy(SsoProvider $ssoProvider)
    {
        $ssoProvider->delete();
        return redirect()->route('admin.sso-providers.index')->with('success', 'SSO provider deleted.');
    }
}
