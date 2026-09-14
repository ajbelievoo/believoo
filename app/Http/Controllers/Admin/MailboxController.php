<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MailDomain;
use App\Models\Mailbox;
use App\Services\MailboxService;
use Illuminate\Http\Request;

class MailboxController extends Controller
{
    public function index()
    {
        $mailboxes = Mailbox::orderByDesc('created')->paginate(20);
        return view('admin.mailboxes.index', compact('mailboxes'));
    }

    public function create()
    {
        $domains = MailDomain::where('active', 1)->pluck('domain', 'domain');
        return view('admin.mailboxes.create', compact('domains'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'local_part' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9._-]+$/'],
            'domain' => ['required', 'string'],
            'full_name' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'quota_gb' => ['nullable', 'numeric', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ]);

        app(MailboxService::class)->createMailbox(
            localPart: $validated['local_part'],
            domain: $validated['domain'],
            password: $validated['password'],
            fullName: $validated['full_name'] ?? '',
            quotaGb: isset($validated['quota_gb']) && $validated['quota_gb'] !== '' ? (float) $validated['quota_gb'] : null,
            active: (bool) ($validated['active'] ?? true),
        );

        return redirect()->route('admin.mailboxes.index')
            ->with('success', 'Email account ' . strtolower($validated['local_part']) . '@' . $validated['domain'] . ' created successfully.');
    }

    public function edit(Mailbox $mailbox)
    {
        return view('admin.mailboxes.edit', compact('mailbox'));
    }

    public function update(Request $request, Mailbox $mailbox)
    {
        $validated = $request->validate([
            'full_name' => ['nullable', 'string', 'max:255'],
            'quota_gb' => ['nullable', 'numeric', 'min:0'],
            'active' => ['nullable', 'boolean'],
            'new_password' => ['nullable', 'string', 'min:8'],
        ]);

        if (!empty($validated['new_password'])) {
            app(MailboxService::class)->resetPassword($mailbox, $validated['new_password']);
        }

        $mailbox->update([
            'full_name' => $validated['full_name'] ?? '',
            'quota' => isset($validated['quota_gb']) && $validated['quota_gb'] !== ''
                ? (int) ((float) $validated['quota_gb'] * 1073741824)
                : 0,
            'active' => (int) (bool) ($validated['active'] ?? false),
            'modified' => now()->format('Y-m-d H:i:s'),
        ]);

        return redirect()->route('admin.mailboxes.index')
            ->with('success', 'Email account ' . $mailbox->username . ' updated.');
    }

    public function destroy(Mailbox $mailbox)
    {
        $username = $mailbox->username;
        app(MailboxService::class)->deleteMailbox($mailbox);

        return redirect()->route('admin.mailboxes.index')
            ->with('success', 'Email account ' . $username . ' deleted.');
    }
}
