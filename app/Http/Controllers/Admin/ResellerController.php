<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reseller;
use App\Models\User;
use Illuminate\Http\Request;

class ResellerController extends Controller
{
    public function index()
    {
        $resellers = Reseller::with('owner')->orderBy('company_name')->paginate(25);
        return view('admin.resellers.index', compact('resellers'));
    }

    public function create()
    {
        $users = User::where('role', 'client')->orderBy('name')->pluck('name', 'id');
        return view('admin.resellers.form', compact('users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'owner_user_id' => 'required|exists:users,id',
            'company_name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:resellers,slug',
            'custom_domain' => 'nullable|string|max:255|unique:resellers,custom_domain',
            'brand_color' => 'nullable|string|max:20',
            'logo_url' => 'nullable|string|max:500',
            'favicon_url' => 'nullable|string|max:500',
            'support_email' => 'nullable|email|max:255',
            'billing_email' => 'nullable|email|max:255',
            'default_margin_percent' => 'nullable|numeric',
            'settings' => 'nullable|json',
            'is_active' => 'boolean',
        ]);

        $data['settings'] = $data['settings'] ? json_decode($data['settings'], true) : [];
        $data['is_active'] = $request->boolean('is_active', true);

        Reseller::create($data);

        return redirect()->route('admin.resellers.index')->with('success', 'Reseller created.');
    }

    public function edit(Reseller $reseller)
    {
        $users = User::where('role', 'client')->orderBy('name')->pluck('name', 'id');
        return view('admin.resellers.form', compact('reseller', 'users'));
    }

    public function update(Request $request, Reseller $reseller)
    {
        $data = $request->validate([
            'owner_user_id' => 'required|exists:users,id',
            'company_name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:resellers,slug,' . $reseller->id,
            'custom_domain' => 'nullable|string|max:255|unique:resellers,custom_domain,' . $reseller->id,
            'brand_color' => 'nullable|string|max:20',
            'logo_url' => 'nullable|string|max:500',
            'favicon_url' => 'nullable|string|max:500',
            'support_email' => 'nullable|email|max:255',
            'billing_email' => 'nullable|email|max:255',
            'default_margin_percent' => 'nullable|numeric',
            'settings' => 'nullable|json',
            'is_active' => 'boolean',
        ]);

        $data['settings'] = $data['settings'] ? json_decode($data['settings'], true) : [];
        $data['is_active'] = $request->boolean('is_active', true);

        $reseller->update($data);

        return redirect()->route('admin.resellers.index')->with('success', 'Reseller updated.');
    }

    public function destroy(Reseller $reseller)
    {
        $reseller->delete();
        return redirect()->route('admin.resellers.index')->with('success', 'Reseller deleted.');
    }

    public function approve(Reseller $reseller)
    {
        $reseller->update(['approved_at' => now(), 'is_active' => true]);
        return redirect()->route('admin.resellers.index')->with('success', 'Reseller approved.');
    }
}
