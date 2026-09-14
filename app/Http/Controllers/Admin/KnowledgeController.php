<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeArticle;
use Illuminate\Http\Request;

class KnowledgeController extends Controller
{
    public function index()
    {
        $articles = KnowledgeArticle::latest()->paginate(20);
        return view('admin.knowledge.index', compact('articles'));
    }

    public function create()
    {
        return view('admin.knowledge.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|min:3|max:255',
            'content' => 'required|min:10',
            'keywords' => 'nullable|max:255',
            'category' => 'nullable|max:100',
            'is_active' => 'boolean',
        ]);
        $validated['is_active'] = $request->boolean('is_active', true);
        KnowledgeArticle::create($validated);
        return redirect()->route('admin.knowledge.index')->with('success', 'Article added — AI will use it now.');
    }

    public function edit(KnowledgeArticle $knowledge)
    {
        return view('admin.knowledge.edit', compact('knowledge'));
    }

    public function update(Request $request, KnowledgeArticle $knowledge)
    {
        $validated = $request->validate([
            'title' => 'required|min:3|max:255',
            'content' => 'required|min:10',
            'keywords' => 'nullable|max:255',
            'category' => 'nullable|max:100',
            'is_active' => 'boolean',
        ]);
        $validated['is_active'] = $request->boolean('is_active');
        $knowledge->update($validated);
        return redirect()->route('admin.knowledge.index')->with('success', 'Article updated.');
    }

    public function destroy(KnowledgeArticle $knowledge)
    {
        $knowledge->delete();
        return redirect()->route('admin.knowledge.index')->with('success', 'Article removed.');
    }
}
