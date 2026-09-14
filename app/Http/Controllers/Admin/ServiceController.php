<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::latest()->paginate(20);
        return view('admin.services.index', compact('services'));
    }

    public function create()
    {
        return view('admin.services.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|unique:services',
            'category' => 'nullable|string|max:100',
            'description' => 'required|string',
            'content' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:2048',
            'price' => 'required|numeric|min:0',
            'price_label' => 'nullable|string|max:50',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:1000',
            'meta_keywords' => 'nullable|string|max:1000',
            'faqs' => 'nullable|array',
            'faqs.*.question' => 'nullable|string|max:500',
            'faqs.*.answer' => 'nullable|string|max:5000',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('services', 'public');
        } else {
            unset($validated['image']);
        }

        Service::create($validated);
        return redirect()->route('admin.services.index')->with('success', 'Service created');
    }

    public function edit(Service $service)
    {
        return view('admin.services.edit', compact('service'));
    }

    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|unique:services,slug,' . $service->id,
            'category' => 'nullable|string|max:100',
            'description' => 'required|string',
            'content' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:2048',
            'price' => 'required|numeric|min:0',
            'price_label' => 'nullable|string|max:50',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:1000',
            'meta_keywords' => 'nullable|string|max:1000',
            'faqs' => 'nullable|array',
            'faqs.*.question' => 'nullable|string|max:500',
            'faqs.*.answer' => 'nullable|string|max:5000',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', $service->is_active);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('services', 'public');
            if ($service->image && Storage::disk('public')->exists($service->image)) {
                Storage::disk('public')->delete($service->image);
            }
        } else {
            unset($validated['image']);
        }

        $service->update($validated);
        return redirect()->route('admin.services.index')->with('success', 'Service updated');
    }

    public function destroy(Service $service)
    {
        if ($service->image && Storage::disk('public')->exists($service->image)) {
            Storage::disk('public')->delete($service->image);
        }
        $service->delete();
        return redirect()->route('admin.services.index')->with('success', 'Service deleted');
    }
}
