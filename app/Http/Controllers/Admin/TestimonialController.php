<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\Request;

class TestimonialController extends Controller
{
    public function index()
    {
        $testimonials = Testimonial::ordered()->paginate(20);
        $pendingCount = Testimonial::where('is_visible', false)->count();
        return view('admin.testimonials.index', compact('testimonials', 'pendingCount'));
    }

    public function create()
    {
        return view('admin.testimonials.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'content' => 'required|string|min:10|max:2000',
            'rating' => 'required|integer|min:1|max:5',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['is_visible'] = $request->boolean('is_visible');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        Testimonial::create($validated);
        return redirect()->route('admin.testimonials.index')->with('success', 'Testimonial created');
    }

    public function edit(Testimonial $testimonial)
    {
        return view('admin.testimonials.edit', compact('testimonial'));
    }

    public function update(Request $request, Testimonial $testimonial)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'content' => 'required|string|min:10|max:2000',
            'rating' => 'required|integer|min:1|max:5',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['is_visible'] = $request->boolean('is_visible');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $testimonial->update($validated);
        return redirect()->route('admin.testimonials.index')->with('success', 'Testimonial updated');
    }

    public function toggle(Testimonial $testimonial)
    {
        $testimonial->update(['is_visible' => ! $testimonial->is_visible]);
        return back()->with('success', $testimonial->is_visible ? 'Testimonial approved & published' : 'Testimonial hidden from site');
    }

    public function destroy(Testimonial $testimonial)
    {
        $testimonial->delete();
        return back()->with('success', 'Testimonial deleted');
    }
}
