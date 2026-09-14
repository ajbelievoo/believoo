<?php
namespace App\Http\Controllers\Bconnect;
use App\Events\BconnectMessageSent;
use App\Http\Controllers\Controller;
use App\Models\Bconnect\Message;
use App\Models\Bconnect\Project;
use App\Models\Bconnect\Ticket;
use Illuminate\Http\Request;

class ChatController extends Controller {
    public function project(Request $r, Project $project) {
        if ($project->company_id != $r->input('bconnect_company_id')) abort(403);
        $messages = Message::where('channel_type', Project::class)->where('channel_id', $project->id)->with('member.user')->latest()->limit(50)->get()->reverse();
        return view('bconnect.chat', compact('project', 'messages'));
    }

    public function ticket(Request $r, Ticket $ticket) {
        if ($ticket->company_id != $r->input('bconnect_company_id')) abort(403);
        $messages = Message::where('channel_type', Ticket::class)->where('channel_id', $ticket->id)->with('member.user')->latest()->limit(50)->get()->reverse();
        return view('bconnect.chat', compact('ticket', 'messages'));
    }

    public function store(Request $r) {
        $data = $r->validate(['channel_type' => 'required', 'channel_id' => 'required|integer', 'message' => 'required']);
        $model = $data['channel_type'] === 'project' ? Project::class : Ticket::class;
        $record = $model::where('company_id', $r->input('bconnect_company_id'))->findOrFail($data['channel_id']);
        $msg = Message::create([
            'company_id' => $r->input('bconnect_company_id'),
            'member_id' => $r->input('bconnect_member')->id,
            'channel_type' => $model,
            'channel_id' => $data['channel_id'],
            'message' => $data['message'],
        ]);
        $msg->load('member.user');
        broadcast(new BconnectMessageSent($msg))->toOthers();
        if ($r->ajax() || $r->wantsJson()) {
            return response()->json(['success' => true, 'id' => $msg->id, 'message' => $msg->message, 'member' => ['name' => $msg->member->user->name], 'member_id' => $msg->member_id, 'created_at' => $msg->created_at->format('H:i')]);
        }
        return back();
    }
}
