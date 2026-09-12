<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\AnnouncementTrackingController;
use App\Mail\AdminAnnouncement;
use App\Models\Announcement;
use App\Services\AnnouncementBroadcastService;
use App\Models\AnnouncementLog;
use App\Models\AnnouncementRecipient;
use App\Models\AnnouncementTemplate;
use App\Models\Bconnect\Member;
use App\Models\EmailPreference;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::with('sender')->orderBy('created_at', 'desc')->paginate(20);
        $templates = AnnouncementTemplate::orderBy('name')->get();
        return view('admin.announcements.index', compact('announcements', 'templates'));
    }

    public function create()
    {
        $templates = AnnouncementTemplate::orderBy('name')->get();
        return view('admin.announcements.create', compact('templates'));
    }

    public function store(Request $request)
    {
        $data = $this->validateAnnouncement($request);

        $data['attachment'] = $this->storeAttachment($request);

        $announcement = Announcement::create($data);

        if ($request->has('save_template')) {
            $this->saveTemplate($request, $announcement);
        }

        if ($announcement->isScheduled()) {
            return redirect()->route('admin.announcements.index')->with('success', 'Announcement saved and scheduled.');
        }

        if ($request->input('action') === 'test') {
            return $this->sendTest($request, $announcement, false);
        }

        if ($announcement->is_published) {
            $this->sendAnnouncement($announcement);
        }

        return redirect()->route('admin.announcements.index')->with('success', 'Announcement saved' . ($announcement->is_published ? ' and broadcasted' : '') . '.');
    }

    public function edit(Announcement $announcement)
    {
        $templates = AnnouncementTemplate::orderBy('name')->get();
        return view('admin.announcements.edit', compact('announcement', 'templates'));
    }

    public function update(Request $request, Announcement $announcement)
    {
        $data = $this->validateAnnouncement($request);

        $data['attachment'] = $this->storeAttachment($request) ?? $announcement->attachment;

        $wasPublished = $announcement->is_published;
        $announcement->update($data);

        if ($request->has('save_template')) {
            $this->saveTemplate($request, $announcement);
        }

        if ($request->input('action') === 'test') {
            return $this->sendTest($request, $announcement, false);
        }

        if ($announcement->isScheduled()) {
            return redirect()->route('admin.announcements.index')->with('success', 'Announcement updated and scheduled.');
        }

        if ($announcement->is_published && (! $wasPublished || $announcement->sent_at === null)) {
            $this->sendAnnouncement($announcement);
        }

        return redirect()->route('admin.announcements.index')->with('success', 'Announcement updated' . ($announcement->is_published && ! $wasPublished ? ' and broadcasted' : '') . '.');
    }

    public function logs(Announcement $announcement)
    {
        $logs = $announcement->logs()->latest()->paginate(50);
        return view('admin.announcements.logs', compact('announcement', 'logs'));
    }

    public function destroy(Announcement $announcement)
    {
        if ($announcement->attachment) {
            Storage::disk('public')->delete($announcement->attachment);
        }
        $announcement->delete();
        return redirect()->route('admin.announcements.index')->with('success', 'Announcement deleted.');
    }

    public function send(Announcement $announcement)
    {
        (new AnnouncementBroadcastService())->send($announcement);
        return redirect()->route('admin.announcements.index')->with('success', 'Announcement broadcasted.');
    }

    public function testSend(Request $request)
    {
        $data = $this->validateAnnouncement($request, true);
        $data['attachment'] = $this->storeAttachment($request);

        $announcement = new Announcement($data);
        $announcement->exists = false;

        return $this->sendTest($request, $announcement, true);
    }

    public function preview(Request $request)
    {
        $data = $this->validateAnnouncement($request, true);
        $announcement = new Announcement($data);
        $announcement->exists = false;

        $html = view('emails.announcement', [
            'announcement' => $announcement,
            'body' => $announcement->messageForLocale($request->input('locale', 'en')) ?? $announcement->message,
            'unsubscribeUrl' => url('/unsubscribe/announcements'),
        ])->render();

        return response($html);
    }

    public function audienceCount(Request $request)
    {
        $audience = $request->input('audience', 'all');
        $emails = $this->collectEmails($audience);

        return response()->json([
            'count' => $emails->count(),
            'emails' => $emails->take(10)->values(),
        ]);
    }

    public function loadTemplate(AnnouncementTemplate $template)
    {
        return response()->json([
            'title' => $template->title,
            'message' => $template->message,
            'message_hi' => $template->message_hi,
            'type' => $template->type,
            'locale' => $template->locale,
        ]);
    }

    private function validateAnnouncement(Request $request, bool $test = false): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'message_hi' => 'nullable|string',
            'title_b' => 'nullable|string|max:255',
            'message_b' => 'nullable|string',
            'message_hi_b' => 'nullable|string',
            'type' => 'required|in:info,warning,important',
            'locale' => 'required|in:en,hi,both',
            'audience' => 'required|in:all,clients,bconnect,ghc',
            'send_type' => 'required|in:now,schedule,draft',
            'segment' => 'nullable|in:admins,clients,paid',
            'throttle_per_minute' => 'nullable|integer|min:0',
            'send_sms' => 'nullable|boolean',
            'send_push' => 'nullable|boolean',
            'ab_enabled' => 'nullable|boolean',
            'ab_test_name' => 'nullable|string|max:255',
            'ab_test_percentage' => 'nullable|integer|min:1|max:100',
            'ab_split' => 'nullable|integer|min:0|max:100',
            'ab_metric' => 'nullable|in:opens,clicks',
            'ab_duration_minutes' => 'nullable|integer|min:1',
            'template_id' => 'nullable|exists:announcement_templates,id',
            'attachment' => 'nullable|file|max:5120',
            'template_name' => 'nullable|string|max:255',
        ];

        if ($request->input('send_type') === 'schedule') {
            $rules['scheduled_at'] = 'required|date|after:now';
        }

        if ($request->boolean('ab_enabled')) {
            $rules['title_b'] = 'required|string|max:255';
            $rules['message_b'] = 'required|string';
        }

        $validated = $request->validate($rules);

        $validated['is_published'] = in_array($request->input('send_type'), ['now', 'schedule']);
        $validated['scheduled_at'] = $request->input('send_type') === 'schedule' ? $request->input('scheduled_at') : null;
        $validated['send_sms'] = $request->boolean('send_sms');
        $validated['send_push'] = $request->boolean('send_push');
        $validated['throttle_per_minute'] = $request->input('throttle_per_minute', 60);
        $validated['ab_enabled'] = $request->boolean('ab_enabled');
        $validated['ab_test_percentage'] = $request->input('ab_test_percentage', 30);
        $validated['ab_split'] = $request->input('ab_split', 50);
        $validated['ab_metric'] = $request->input('ab_metric', 'opens');
        $validated['ab_duration_minutes'] = $request->input('ab_duration_minutes', 60);
        $validated['ab_status'] = $request->boolean('ab_enabled') ? 'draft' : null;

        return $validated;
    }

    private function storeAttachment(Request $request): ?string
    {
        if (! $request->hasFile('attachment')) {
            return null;
        }

        return $request->file('attachment')->store('announcements', 'public');
    }

    private function saveTemplate(Request $request, Announcement $announcement): void
    {
        if (! $request->filled('template_name')) {
            return;
        }

        AnnouncementTemplate::create([
            'name' => $request->input('template_name'),
            'title' => $announcement->title,
            'message' => $announcement->message,
            'message_hi' => $announcement->message_hi,
            'type' => $announcement->type,
            'locale' => $announcement->locale,
            'created_by' => Auth::id(),
        ]);
    }

    private function sendTest(Request $request, Announcement $announcement, bool $isDraft): mixed
    {
        $email = Auth::user()?->email ?? config('mail.from.address');

        try {
            Mail::to($email)->send(new AdminAnnouncement($announcement, $email));
            return back()->with('success', 'Test email sent to ' . $email);
        } catch (\Throwable $e) {
            return back()->with('error', 'Test email failed: ' . $e->getMessage());
        }
    }

    public function sendAnnouncement(Announcement $announcement): void
    {
        (new AnnouncementBroadcastService())->send($announcement);
    }

    private function collectEmails(string $audience): mixed
    {
        $emails = collect();

        if (in_array($audience, ['all', 'clients'])) {
            $query = User::whereNotNull('email')->where('email', '!=', '');
            if ($audience === 'clients') {
                $query->where('is_admin', false);
            }
            $emails = $emails->merge($query->pluck('email'));
        }

        if (in_array($audience, ['all', 'bconnect'])) {
            $emails = $emails->merge(
                Member::with('user')
                    ->whereHas('user', fn($q) => $q->whereNotNull('email')->where('email', '!=', ''))
                    ->get()
                    ->pluck('user.email')
            );
        }

        if (in_array($audience, ['all', 'ghc'])) {
            try {
                $emails = $emails->merge($this->ghcUserEmails());
            } catch (\Throwable $e) {
                logger()->warning('Could not fetch GHC user emails for announcement: ' . $e->getMessage());
            }
        }

        return $emails->unique()->filter()->values();
    }

    private function detectProduct(string $email): string
    {
        if (User::where('email', $email)->exists()) {
            return 'believoo';
        }
        if (Member::whereHas('user', fn($q) => $q->where('email', $email))->exists()) {
            return 'bconnect';
        }
        return 'ghc';
    }

    private function log(Announcement $announcement, ?string $email, ?string $product, string $level, string $message): void
    {
        AnnouncementLog::create([
            'announcement_id' => $announcement->id,
            'email' => $email,
            'product' => $product,
            'level' => $level,
            'message' => $message,
        ]);
    }

    private function ghcUserEmails(): array
    {
        $path = config('services.ghc.database_path', '/www/wwwroot/ghc/python-backend/believoo_dev.db');
        if (! file_exists($path)) {
            return [];
        }

        try {
            $pdo = new \PDO('sqlite:' . $path);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $stmt = $pdo->query("SELECT email FROM users WHERE email IS NOT NULL AND email != '' AND (is_suspended = 0 OR is_suspended IS NULL)");
            return $stmt->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\Throwable $e) {
            logger()->warning('Could not read GHC user emails from SQLite: ' . $e->getMessage());
            return [];
        }
    }
}
