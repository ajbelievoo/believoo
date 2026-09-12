<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\AnnouncementLog;
use App\Models\AnnouncementRecipient;
use App\Models\Bconnect\Member;
use App\Models\EmailPreference;
use App\Models\PushSubscription;
use App\Models\User;
use App\Mail\AdminAnnouncement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Minishlink\WebPush\Subscription as WebPushSubscription;
use Minishlink\WebPush\WebPush;

class AnnouncementBroadcastService
{
    public function send(Announcement $announcement): void
    {
        $emails = $this->collectEmails($announcement);
        if ($emails->isEmpty()) {
            $this->log($announcement, null, null, 'warning', 'No recipients found');
            return;
        }

        $emails = $emails->filter(fn ($email) => ! $this->isUnsubscribed($email))->values();
        if ($emails->isEmpty()) {
            $this->log($announcement, null, null, 'warning', 'All recipients are unsubscribed');
            return;
        }

        if ($announcement->ab_enabled) {
            $this->sendAbTest($announcement, $emails);
            return;
        }

        $this->sendBroadcast($announcement, $emails, false);
    }

    public function sendWinner(Announcement $announcement, string $winner): void
    {
        $reserveRecipients = AnnouncementRecipient::where('announcement_id', $announcement->id)
            ->where('is_test', false)
            ->whereNull('sent_at')
            ->get();

        if ($reserveRecipients->isEmpty()) {
            $this->completeAbTest($announcement, $winner);
            return;
        }

        $delayMicros = $this->throttleDelay($announcement->throttle_per_minute ?: 60);

        foreach ($reserveRecipients as $recipient) {
            $recipient->update(['variant' => $winner]);

            $user = User::where('email', $recipient->email)->first();
            $locale = $user?->locale ?? $this->emailLocale($recipient->email) ?? $announcement->locale;

            try {
                if ($announcement->send_sms && $user?->phone && $this->smsEnabledFor($recipient->email)) {
                    SmsService::send($user->phone, strip_tags($announcement->messageForLocaleAndVariant($locale, $winner) ?? $announcement->message));
                }
            } catch (\Throwable $e) {
                $this->log($announcement, $recipient->email, $recipient->product, 'error', 'SMS: ' . $e->getMessage());
            }

            try {
                Mail::to($recipient->email)->send(new AdminAnnouncement($announcement, $recipient->email, $recipient, $locale));
                $recipient->update(['sent_at' => now()]);
            } catch (\Throwable $e) {
                $this->log($announcement, $recipient->email, $recipient->product, 'error', $e->getMessage());
            }

            if ($announcement->send_push) {
                $this->sendPush($announcement, $recipient->email, $locale);
            }

            if ($delayMicros > 0) {
                usleep($delayMicros);
            }
        }

        $this->completeAbTest($announcement, $winner);
    }

    private function sendAbTest(Announcement $announcement, Collection $emails): void
    {
        $total = $emails->count();
        $testPercentage = max(1, min(100, $announcement->ab_test_percentage ?: 30));
        $testCount = (int) round($total * $testPercentage / 100);
        $testCount = max(1, min($total, $testCount));

        $shuffled = $emails->shuffle()->values();
        $testEmails = $shuffled->slice(0, $testCount)->values();
        $reserveEmails = $shuffled->slice($testCount)->values();

        $split = max(0, min(100, $announcement->ab_split ?: 50));
        $variantACount = (int) round($testCount * $split / 100);
        $variantACount = max(0, min($testCount, $variantACount));

        $emailsA = $testEmails->slice(0, $variantACount)->values();
        $emailsB = $testEmails->slice($variantACount)->values();

        // Create reserve recipients up front so the winner can be sent later.
        foreach ($reserveEmails as $email) {
            AnnouncementRecipient::firstOrCreate(
                ['announcement_id' => $announcement->id, 'email' => $email],
                ['product' => $this->detectProduct($email), 'is_test' => false]
            );
        }

        $delayMicros = $this->throttleDelay($announcement->throttle_per_minute ?: 60);

        $this->sendVariantBatch($announcement, $emailsA, 'A', $delayMicros);
        $this->sendVariantBatch($announcement, $emailsB, 'B', $delayMicros);

        $announcement->update([
            'is_published' => true,
            'sent_at' => now(),
            'sent_by' => Auth::id() ?? $announcement->sent_by,
            'ab_status' => 'testing',
            'ab_test_started_at' => now(),
        ]);

        $this->log($announcement, null, null, 'info', "A/B test started: {$emailsA->count()} A / {$emailsB->count()} B / {$reserveEmails->count()} reserve");
    }

    private function sendVariantBatch(Announcement $announcement, Collection $emails, string $variant, int $delayMicros): void
    {
        foreach ($emails as $email) {
            $user = User::where('email', $email)->first();
            $locale = $user?->locale ?? $this->emailLocale($email) ?? $announcement->locale;

            $recipient = AnnouncementRecipient::firstOrCreate(
                ['announcement_id' => $announcement->id, 'email' => $email],
                ['product' => $this->detectProduct($email), 'variant' => $variant, 'is_test' => true]
            );

            if ($recipient->wasRecentlyCreated === false) {
                $recipient->update(['variant' => $variant, 'is_test' => true]);
            }

            try {
                if ($announcement->send_sms && $user?->phone && $this->smsEnabledFor($email)) {
                    SmsService::send($user->phone, strip_tags($announcement->messageForLocaleAndVariant($locale, $variant) ?? $announcement->message));
                }
            } catch (\Throwable $e) {
                $this->log($announcement, $email, $recipient->product, 'error', 'SMS: ' . $e->getMessage());
            }

            try {
                Mail::to($email)->send(new AdminAnnouncement($announcement, $email, $recipient, $locale));
                $recipient->update(['sent_at' => now()]);
            } catch (\Throwable $e) {
                $this->log($announcement, $email, $recipient->product, 'error', $e->getMessage());
            }

            if ($announcement->send_push) {
                $this->sendPush($announcement, $email, $locale, $variant);
            }

            if ($delayMicros > 0) {
                usleep($delayMicros);
            }
        }
    }

    private function sendBroadcast(Announcement $announcement, Collection $emails, bool $isAbReserve = false): void
    {
        $delayMicros = $this->throttleDelay($announcement->throttle_per_minute ?: 60);

        foreach ($emails as $email) {
            $user = User::where('email', $email)->first();
            $locale = $user?->locale ?? $this->emailLocale($email) ?? $announcement->locale;
            $product = $this->detectProduct($email);

            $recipient = AnnouncementRecipient::firstOrCreate(
                ['announcement_id' => $announcement->id, 'email' => $email],
                ['product' => $product]
            );

            try {
                if ($announcement->send_sms && $user?->phone && $this->smsEnabledFor($email)) {
                    SmsService::send($user->phone, strip_tags($announcement->messageForLocale($locale) ?? $announcement->message));
                }
            } catch (\Throwable $e) {
                $this->log($announcement, $email, $product, 'error', 'SMS: ' . $e->getMessage());
            }

            try {
                Mail::to($email)->send(new AdminAnnouncement($announcement, $email, $recipient, $locale));
                $recipient->update(['sent_at' => now()]);
            } catch (\Throwable $e) {
                $this->log($announcement, $email, $product, 'error', $e->getMessage());
            }

            if ($announcement->send_push) {
                $this->sendPush($announcement, $email, $locale);
            }

            if ($delayMicros > 0) {
                usleep($delayMicros);
            }
        }

        $announcement->update([
            'is_published' => true,
            'sent_at' => now(),
            'sent_by' => Auth::id() ?? $announcement->sent_by,
        ]);

        $this->log($announcement, null, null, 'info', 'Sent to ' . $emails->count() . ' recipients');
    }

    private function completeAbTest(Announcement $announcement, string $winner): void
    {
        $announcement->update([
            'ab_status' => 'completed',
            'ab_winner' => $winner,
            'ab_winner_sent_at' => now(),
        ]);

        $this->log($announcement, null, null, 'info', 'A/B winner sent: variant ' . $winner);
    }

    private function collectEmails(Announcement $a): Collection
    {
        $emails = collect();

        if (in_array($a->audience, ['all', 'clients'])) {
            $query = User::whereNotNull('email')->where('email', '!=', '');
            if ($a->audience === 'clients') {
                $query->where('is_admin', false);
            }
            if ($a->segment === 'admins') {
                $query->where('is_admin', true);
            } elseif ($a->segment === 'clients') {
                $query->where('is_admin', false);
            } elseif ($a->segment === 'paid') {
                $query->whereHas('orders', fn($q) => $q->where('status', 'paid'));
            }
            $emails = $emails->merge($query->pluck('email'));
        }

        if (in_array($a->audience, ['all', 'bconnect'])) {
            $emails = $emails->merge(
                Member::with('user')
                    ->whereHas('user', fn($q) => $q->whereNotNull('email')->where('email', '!=', ''))
                    ->get()
                    ->pluck('user.email')
            );
        }

        if (in_array($a->audience, ['all', 'ghc'])) {
            $emails = $emails->merge($this->ghcEmails());
        }

        return $emails->unique()->filter()->values();
    }

    private function isUnsubscribed(string $email): bool
    {
        $preference = EmailPreference::where('email', $email)->first();
        return $preference && $preference->announcements === false;
    }

    private function throttleDelay(int $perMinute): int
    {
        if ($perMinute <= 0) return 0;
        return (int) ((60 * 1000 * 1000) / $perMinute);
    }

    private function emailLocale(string $email): ?string
    {
        return EmailPreference::where('email', $email)->value('locale') ?? User::where('email', $email)->value('locale');
    }

    private function smsEnabledFor(string $email): bool
    {
        return EmailPreference::where('email', $email)->value('sms') ?? true;
    }

    private function sendPush(Announcement $a, string $email, string $locale, string $variant = 'A'): void
    {
        $user = User::where('email', $email)->first();
        if (! $user) return;

        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
        $public = $settings['vapid_public_key'] ?? '';
        $private = $settings['vapid_private_key'] ?? '';
        if (! $public || ! $private) return;

        $subs = PushSubscription::where('user_id', $user->id)
            ->orWhere('session_id', (string) $user->id)
            ->get();
        if ($subs->isEmpty()) return;

        $webPush = new WebPush(['VAPID' => ['subject' => 'mailto:support@believoo.com', 'publicKey' => $public, 'privateKey' => $private]]);
        $payload = json_encode([
            'title' => $a->titleForVariant($variant),
            'body' => strip_tags($a->messageForLocaleAndVariant($locale, $variant) ?? $a->message),
            'url' => config('app.url'),
            'icon' => config('app.url') . '/images/believoo-email-logo.png',
        ]);

        foreach ($subs as $s) {
            $sub = json_decode($s->keys, true);
            $sub['endpoint'] = $s->endpoint;
            $webPush->queueNotification(WebPushSubscription::create($sub), $payload);
        }
    }

    private function ghcEmails(): array
    {
        $path = config('services.ghc.database_path', '/www/wwwroot/ghc/python-backend/believoo_dev.db');
        if (! file_exists($path)) return [];
        try {
            $pdo = new \PDO('sqlite:' . $path);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $stmt = $pdo->query("SELECT email FROM users WHERE email IS NOT NULL AND email != '' AND (is_suspended = 0 OR is_suspended IS NULL)");
            return $stmt->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\Throwable $e) {
            return [];
        }
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

    private function log(Announcement $a, ?string $email, ?string $product, string $level, string $message): void
    {
        AnnouncementLog::create([
            'announcement_id' => $a->id,
            'email' => $email,
            'product' => $product,
            'level' => $level,
            'message' => $message,
        ]);
    }
}
