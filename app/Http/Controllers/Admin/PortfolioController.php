<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PortfolioController extends Controller
{
    public function index()
    {
        $portfolios = Portfolio::latest()->paginate(20);
        return view('admin.portfolio.index', compact('portfolios'));
    }

    public function create()
    {
        return view('admin.portfolio.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|unique:portfolios',
            'description' => 'required|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:2048',
            'image_url' => 'nullable|string|max:500',
            'is_visible' => 'boolean',
        ]);

        $validated['image'] = $this->handleImage($request, null, $validated['image_url'] ?? null);
        unset($validated['image_url']);

        Portfolio::create($validated);
        return redirect()->route('admin.portfolio.index')->with('success', 'Portfolio item created');
    }

    public function edit(Portfolio $portfolio)
    {
        return view('admin.portfolio.edit', compact('portfolio'));
    }

    public function update(Request $request, Portfolio $portfolio)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|unique:portfolios,slug,' . $portfolio->id,
            'description' => 'required|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:2048',
            'image_url' => 'nullable|string|max:500',
            'is_visible' => 'boolean',
        ]);

        $validated['image'] = $this->handleImage($request, $portfolio->image, $validated['image_url'] ?? null);
        unset($validated['image_url']);

        $portfolio->update($validated);
        return redirect()->route('admin.portfolio.index')->with('success', 'Portfolio item updated');
    }

    public function destroy(Portfolio $portfolio)
    {
        $portfolio->delete();
        return redirect()->route('admin.portfolio.index')->with('success', 'Portfolio item deleted');
    }

    private function handleImage(Request $request, $existingImage = null, $imageUrl = null)
    {
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $path = $file->store('portfolio', 'public');
            if (is_string($existingImage) && $existingImage && Storage::disk('public')->exists($existingImage)) {
                Storage::disk('public')->delete($existingImage);
            }
            return $path;
        }

        if (!empty($imageUrl)) {
            return $imageUrl;
        }

        return is_string($existingImage) ? $existingImage : null;
    }
}
