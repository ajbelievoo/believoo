<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function index()
    {
        $stores = Store::latest()->paginate(20);
        return view('admin.stores.index', compact('stores'));
    }

    public function create()
    {
        return view('admin.stores.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url',
            'status' => 'required|in:active,inactive',
        ]);

        Store::create($validated);
        return redirect()->route('admin.stores.index')->with('success', 'Store created');
    }

    public function edit(Store $store)
    {
        return view('admin.stores.edit', compact('store'));
    }

    public function update(Request $request, Store $store)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url',
            'status' => 'required|in:active,inactive',
        ]);

        $store->update($validated);
        return redirect()->route('admin.stores.index')->with('success', 'Store updated');
    }

    public function destroy(Store $store)
    {
        $store->delete();
        return redirect()->route('admin.stores.index')->with('success', 'Store deleted');
    }
}
