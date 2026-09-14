<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::latest()->paginate(20);
        return view('admin.posts.index', compact('posts'));
    }

    public function create()
    {
        return view('admin.posts.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|unique:posts|max:255',
            'excerpt' => 'nullable|string|max:1000',
            'content' => 'required|string',
            'featured_image' => 'nullable|url|max:500',
            'featured_image_file' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:2048',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:1000',
            'meta_keywords' => 'nullable|string|max:1000',
            'is_published' => 'boolean',
            'published_at' => 'nullable|date',
        ]);

        $validated['is_published'] = $request->boolean('is_published', false);
        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['title']);

        if ($request->hasFile('featured_image_file')) {
            $validated['featured_image'] = $request->file('featured_image_file')->store('posts', 'public');
            unset($validated['featured_image_file']);
        } elseif (empty($validated['featured_image'])) {
            unset($validated['featured_image']);
        }

        Post::create($validated);

        return redirect()->route('admin.posts.index')->with('success', 'Post created');
    }

    public function show(Post $post)
    {
        return redirect()->route('admin.posts.edit', $post);
    }

    public function edit(Post $post)
    {
        return view('admin.posts.edit', compact('post'));
    }

    public function update(Request $request, Post $post)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|unique:posts,slug,' . $post->id . '|max:255',
            'excerpt' => 'nullable|string|max:1000',
            'content' => 'required|string',
            'featured_image' => 'nullable|url|max:500',
            'featured_image_file' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:2048',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:1000',
            'meta_keywords' => 'nullable|string|max:1000',
            'is_published' => 'boolean',
            'published_at' => 'nullable|date',
        ]);

        $validated['is_published'] = $request->boolean('is_published', $post->is_published);
        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['title']);

        if ($request->hasFile('featured_image_file')) {
            $validated['featured_image'] = $request->file('featured_image_file')->store('posts', 'public');
            if ($post->featured_image && !str_starts_with($post->featured_image, 'http')) {
                Storage::disk('public')->delete($post->featured_image);
            }
            unset($validated['featured_image_file']);
        } elseif ($request->input('remove_featured_image')) {
            if ($post->featured_image && !str_starts_with($post->featured_image, 'http')) {
                Storage::disk('public')->delete($post->featured_image);
            }
            $validated['featured_image'] = null;
            unset($validated['remove_featured_image']);
        } elseif (empty($validated['featured_image'])) {
            unset($validated['featured_image']);
        }

        $post->update($validated);

        return redirect()->route('admin.posts.index')->with('success', 'Post updated');
    }

    public function destroy(Post $post)
    {
        $post->delete();
        return redirect()->route('admin.posts.index')->with('success', 'Post deleted');
    }
}
