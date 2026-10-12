<?php

namespace App\Http\Controllers\Bconnect;

use App\Events\BconnectMessageSent;
use App\Events\BconnectTyping;
use App\Http\Controllers\Controller;
use App\Models\Bconnect\ChatChannel;
use App\Models\Bconnect\Member;
use App\Models\Bconnect\Message;
use App\Models\Bconnect\Notification;
use App\Models\Bconnect\Project;
use App\Models\Bconnect\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller {

    protected function channelData(Request $r, Project|Ticket|ChatChannel $channel): array
    {
        $memberId = $r->input('bconnect_member')->id;
        $companyId = $r->input('bconnect_company_id');

        $search = $r->input('search');
        $query = Message::where('channel_type', get_class($channel))
            ->where('channel_id', $channel->id)
            ->whereNull('parent_id')->whereNull('deleted_at')
            ->with(['member.user', 'replies.member.user'])
            ->latest();

        if ($search) {
            $query->where('message', 'like', '%' . $search . '%');
        }

        $messages = $query->limit(100)->get()->reverse();

        foreach ($messages as $m) {
            $m->is_read = $m->isReadBy($memberId);
        }

        $unreadCount = Message::where('channel_type', get_class($channel))
            ->where('channel_id', $channel->id)
            ->where('member_id', '!=', $memberId)
            ->where(function ($q) use ($memberId) {
                $q->whereNull('read_by')->orWhereRaw('JSON_CONTAINS(read_by, ?) = 0', [json_encode($memberId)]);
            })
            ->count();

        $members = Member::where('company_id', $companyId)->with('user')->get();

        return [
            'messages' => $messages,
            'unreadCount' => $unreadCount,
            'members' => $members,
            'search' => $search,
            'channel' => $channel,
            'memberId' => $memberId,
        ];
    }

    public function project(Request $r, Project $project) {
        if ($project->company_id != $r->input('bconnect_company_id')) abort(403);
        extract($this->channelData($r, $project));
        return view('bconnect.chat', compact('project', 'messages', 'unreadCount', 'members', 'search', 'memberId'));
    }

    public function ticket(Request $r, Ticket $ticket) {
        if ($ticket->company_id != $r->input('bconnect_company_id')) abort(403);
        extract($this->channelData($r, $ticket));
        return view('bconnect.chat', compact('ticket', 'messages', 'unreadCount', 'members', 'search', 'memberId'));
    }

    // ── Standalone channels / group rooms ──
    public function index(Request $r) {
        $companyId = $r->input('bconnect_company_id');
        $memberId = $r->input('bconnect_member')->id;
        $channels = ChatChannel::where('company_id', $companyId)
            ->with('creator.user')
            ->latest()->get()
            ->filter(fn ($c) => $c->canView($memberId))
            ->values();
        $members = Member::where('company_id', $companyId)->with('user')->get();
        return view('bconnect.channels', compact('channels', 'members'));
    }

    public function storeChannel(Request $r) {
        $data = $r->validate([
            'name' => 'required|string|min:2|max:80',
            'is_private' => 'nullable|boolean',
            'member_ids' => 'nullable|array|max:200',
            'member_ids.*' => 'integer',
        ]);
        $me = $r->input('bconnect_member');
        $private = !empty($data['is_private']);
        $ids = $private ? array_values(array_unique(array_merge([$me->id], array_map('intval', $data['member_ids'] ?? [])))) : null;
        $channel = ChatChannel::create([
            'company_id' => $r->input('bconnect_company_id'),
            'created_by' => $me->id,
            'name' => trim($data['name']),
            'is_private' => $private,
            'member_ids' => $ids,
        ]);
        return redirect()->route('bconnect.chat.channel', $channel->id);
    }

    public function channel(Request $r, ChatChannel $channel) {
        if ($channel->company_id != $r->input('bconnect_company_id')) abort(403);
        if (!$channel->canView($r->input('bconnect_member')->id)) abort(403);
        extract($this->channelData($r, $channel));
        return view('bconnect.chat', compact('channel', 'messages', 'unreadCount', 'members', 'search', 'memberId'));
    }

    // ── Message edit / delete ──
    public function update(Request $r, Message $message) {
        if ($message->company_id != $r->input('bconnect_company_id')) abort(403);
        if ($message->member_id != $r->input('bconnect_member')->id) abort(403);
        if ($message->deleted_at) abort(404);
        $data = $r->validate(['message' => 'required|string|max:10000']);
        $message->message = $data['message'];
        $message->edited_at = now();
        $message->save();
        return response()->json(['success' => true, 'message' => $message->message]);
    }

    public function destroy(Request $r, Message $message) {
        if ($message->company_id != $r->input('bconnect_company_id')) abort(403);
        $me = $r->input('bconnect_member');
        if ($message->member_id != $me->id && $me->role !== 'admin' && $me->role !== 'owner') abort(403);
        foreach ((array) $message->attachments as $p) Storage::disk('public')->delete($p);
        foreach ($message->replies as $reply) {
            foreach ((array) $reply->attachments as $p) Storage::disk('public')->delete($p);
            $reply->delete();
        }
        $message->delete();
        return response()->json(['success' => true]);
    }

    public function store(Request $r) {
        $data = $r->validate([
            'channel_type' => 'required|in:project,ticket,channel',
            'channel_id' => 'required|integer',
            'message' => 'nullable|string|max:10000',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240',
            'parent_id' => 'nullable|integer|exists:bconnect_messages,id',
        ]);

        if (empty($data['message']) && (!$r->hasFile('attachments') || count($r->file('attachments', [])) === 0)) {
            return response()->json(['error' => 'Message or attachment is required.'], 422);
        }

        $model = match ($data['channel_type']) { 'project' => Project::class, 'ticket' => Ticket::class, default => ChatChannel::class };
        $record = $model::where('company_id', $r->input('bconnect_company_id'))->findOrFail($data['channel_id']);
        if ($record instanceof ChatChannel && !$record->canView($r->input('bconnect_member')->id)) abort(403);

        $attachments = [];
        if ($r->hasFile('attachments')) {
            $companyId = $r->input('bconnect_company_id');
            foreach ($r->file('attachments') as $file) {
                $attachments[] = $file->store("bconnect/{$companyId}/chat", 'public');
            }
        }

        $messageText = $data['message'] ?? '';
        $mentions = $this->parseMentions($messageText, $r->input('bconnect_company_id'));

        $msg = Message::create([
            'company_id' => $r->input('bconnect_company_id'),
            'member_id' => $r->input('bconnect_member')->id,
            'channel_type' => $model,
            'channel_id' => $data['channel_id'],
            'parent_id' => $data['parent_id'] ?? null,
            'message' => $messageText,
            'attachments' => $attachments,
            'mentions' => $mentions,
            'read_by' => [$r->input('bconnect_member')->id],
        ]);
        $msg->load('member.user');

        broadcast(new BconnectMessageSent($msg))->toOthers();

        $this->createMentionNotifications($msg, $mentions, $r->input('bconnect_member'));

        $response = [
            'success' => true,
            'id' => $msg->id,
            'message' => $msg->message,
            'attachments' => $msg->attachments ?? [],
            'member' => ['name' => $msg->member->user->name],
            'member_id' => $msg->member_id,
            'parent_id' => $msg->parent_id,
            'mentions' => $mentions,
            'created_at' => $msg->created_at->format('H:i'),
        ];

        if ($r->ajax() || $r->wantsJson()) {
            return response()->json($response);
        }
        return back();
    }

    public function markRead(Request $r, Message $message) {
        if ($message->company_id != $r->input('bconnect_company_id')) abort(403);
        $message->markReadBy($r->input('bconnect_member')->id);
        return response()->json(['success' => true]);
    }

    public function search(Request $r) {
        $r->validate(['channel_type' => 'required|in:project,ticket,channel', 'channel_id' => 'required|integer', 'q' => 'required|string']);
        $model = match ($r->channel_type) { 'project' => Project::class, 'ticket' => Ticket::class, default => ChatChannel::class };
        $channel = $model::where('company_id', $r->input('bconnect_company_id'))->findOrFail($r->channel_id);
        if ($channel instanceof ChatChannel && !$channel->canView($r->input('bconnect_member')->id)) abort(403);
        $messages = Message::where('channel_type', $model)
            ->where('channel_id', $channel->id)
            ->where('message', 'like', '%' . $r->q . '%')
            ->with('member.user')
            ->latest()
            ->limit(50)
            ->get();
        return response()->json(['messages' => $messages->map(fn ($m) => [
            'id' => $m->id,
            'message' => $m->message,
            'member' => ['name' => $m->member->user->name],
            'created_at' => $m->created_at->format('M d, Y H:i'),
            'url' => $r->channel_type === 'project'
                ? route('bconnect.projects.chat', $channel->id) . '?highlight=' . $m->id
                : route('bconnect.tickets.show', $channel->id) . '?chat=1&highlight=' . $m->id,
        ])]);
    }

    public function typing(Request $r) {
        $data = $r->validate(['channel_type' => 'required|in:project,ticket,channel', 'channel_id' => 'required|integer']);
        $member = $r->input('bconnect_member');
        broadcast(new BconnectTyping(
            $r->input('bconnect_company_id'),
            $data['channel_type'],
            $data['channel_id'],
            $member->id,
            $member->user->name
        ))->toOthers();
        return response()->json(['success' => true]);
    }

    public function poll(Request $r) {
        $data = $r->validate(['channel_type' => 'required|in:project,ticket,channel', 'channel_id' => 'required|integer', 'after_id' => 'nullable|integer']);
        $model = match ($data['channel_type']) { 'project' => Project::class, 'ticket' => Ticket::class, default => ChatChannel::class };
        $channel = $model::where('company_id', $r->input('bconnect_company_id'))->findOrFail($data['channel_id']);
        if ($channel instanceof ChatChannel && !$channel->canView($r->input('bconnect_member')->id)) abort(403);
        $memberId = $r->input('bconnect_member')->id;

        $query = Message::where('channel_type', $model)
            ->where('channel_id', $channel->id)
            ->whereNull('parent_id')->whereNull('deleted_at')
            ->with(['member.user', 'replies.member.user'])
            ->orderBy('id');

        if (!empty($data['after_id'])) {
            $query->where('id', '>', $data['after_id']);
        }

        $messages = $query->limit(50)->get();

        foreach ($messages as $m) {
            $m->is_read = $m->isReadBy($memberId);
        }

        return response()->json(['messages' => $messages->map(fn ($m) => [
            'id' => $m->id,
            'message' => $m->message,
            'attachments' => $m->attachments ?? [],
            'member_id' => $m->member_id,
            'member' => ['name' => $m->member->user->name],
            'parent_id' => $m->parent_id,
            'mentions' => $m->mentions ?? [],
            'created_at' => $m->created_at->format('H:i'),
            'replies' => $m->replies->map(fn ($reply) => [
                'id' => $reply->id,
                'message' => $reply->message,
                'member' => ['name' => $reply->member->user->name],
                'created_at' => $reply->created_at->format('H:i'),
            ])->values(),
        ])]);
    }

    protected function parseMentions(string $text, int $companyId): array
    {
        preg_match_all('/@([a-zA-Z0-9_\-\.\s]+?)@|@([a-zA-Z0-9_\-]+)/', $text, $matches);
        $names = array_filter(array_merge($matches[1], $matches[2]));
        $ids = [];
        foreach ($names as $name) {
            $name = trim($name);
            if (!$name) continue;
            $member = Member::where('company_id', $companyId)
                ->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$name}%"))
                ->first();
            if ($member) $ids[] = $member->id;
        }
        return array_values(array_unique($ids));
    }

    protected function createMentionNotifications(Message $msg, array $mentions, Member $sender): void
    {
        foreach ($mentions as $memberId) {
            if ($memberId === $sender->id) continue;
            $channelName = $msg->channel_type === Project::class ? 'project' : ($msg->channel_type === ChatChannel::class ? 'channel' : 'ticket');
            $channelId = $msg->channel_id;
            $url = match (true) {
                $msg->channel_type === Project::class => route('bconnect.projects.chat', $channelId),
                $msg->channel_type === ChatChannel::class => route('bconnect.chat.channel', $channelId),
                default => route('bconnect.tickets.show', $channelId) . '?chat=1',
            };
            Notification::create([
                'company_id' => $msg->company_id,
                'member_id' => $memberId,
                'type' => 'mention',
                'title' => 'You were mentioned',
                'message' => $sender->user->name . ' mentioned you in a ' . $channelName . ' chat.',
                'url' => $url,
            ]);
            $target = Member::with('user')->find($memberId);
            if ($target?->user?->email) {
                \App\Services\BconnectMail::toMember($target,
                    $sender->user->name . ' mentioned you in Bmydesk',
                    'You were mentioned',
                    ['Hi ' . ($target->user->name ?? 'there') . ',', '<strong>' . e($sender->user->name) . '</strong> mentioned you in a ' . $channelName . ' chat:', '<blockquote style="border-left:4px solid #00b7ff;padding-left:12px;margin:12px 0;color:#334155;">' . e(\Illuminate\Support\Str::limit($msg->message, 280)) . '</blockquote>'],
                    url($url),
                    'Open chat'
                );
            }
        }
    }
}
