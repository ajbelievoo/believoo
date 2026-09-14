<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\ChatFlow;
use Illuminate\Http\Request;
class ChatFlowController extends Controller {
    public function index() { return view('admin.chat-flows.index', ['flows' => ChatFlow::orderBy('name')->orderBy('step_order')->paginate(25)]); }
    public function create() { return view('admin.chat-flows.create'); }
    public function store(Request $r) {
        ChatFlow::create($r->validate(['name' => 'required', 'trigger_keywords' => 'required', 'step_order' => 'integer', 'step_type' => 'required', 'content' => 'required', 'next_step' => 'nullable', 'is_active' => 'boolean']));
        return redirect()->route('admin.chat-flows.index')->with('success', 'Flow step added');
    }
    public function edit(ChatFlow $chatFlow) { return view('admin.chat-flows.edit', compact('chatFlow')); }
    public function update(Request $r, ChatFlow $chatFlow) {
        $chatFlow->update($r->validate(['name' => 'required', 'trigger_keywords' => 'required', 'step_order' => 'integer', 'step_type' => 'required', 'content' => 'required', 'next_step' => 'nullable', 'is_active' => 'boolean']));
        return redirect()->route('admin.chat-flows.index')->with('success', 'Updated');
    }
    public function destroy(ChatFlow $chatFlow) { $chatFlow->delete(); return back()->with('success', 'Deleted'); }
}
