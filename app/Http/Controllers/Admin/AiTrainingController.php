<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\AiCorrection;
use Illuminate\Http\Request;
class AiTrainingController extends Controller {
    public function index() {
        $corrections = AiCorrection::latest()->paginate(25);
        $applied = AiCorrection::where('applied', true)->count();
        $pending = AiCorrection::where('applied', false)->count();
        return view('admin.ai-training.index', compact('corrections', 'applied', 'pending'));
    }
    public function update(Request $request, AiCorrection $correction) {
        $correction->update($request->all());
        return back()->with('success', 'Updated');
    }
    public function destroy(AiCorrection $correction) {
        $correction->delete();
        return back()->with('success', 'Deleted');
    }
    public function apply(AiCorrection $correction) {
        $correction->update(['applied' => true]);
        // Add to knowledge base as admin answer
        \App\Models\KnowledgeArticle::create([
            'title' => 'AI Training: ' . substr($correction->question, 0, 50),
            'content' => $correction->correct_answer,
            'keywords' => $correction->question,
            'is_active' => true,
        ]);
        return back()->with('success', 'Applied to Knowledge Base');
    }
}
