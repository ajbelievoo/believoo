<?php

namespace App\Http\Controllers;

use App\Models\Post;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::published()->latest('published_at')->paginate(12);
        return view('blog.index', compact('posts'));
    }

    public function show(Post $post)
    {
        if (!$post->is_published || !$post->published_at || $post->published_at->isFuture()) {
            abort(404);
        }

        $related = Post::published()
            ->where('id', '!=', $post->id)
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('blog.show', compact('post', 'related'));
    }
}
