<?php

namespace App\Services;

use App\Mail\BconnectNotification;
use App\Models\Bconnect\Member;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class BconnectMail
{
    public static function toEmail(string $email, string $subject, string $heading, array $lines, ?string $url = null, ?string $button = null): void
    {
        try {
            Mail::to($email)->send(new BconnectNotification($subject, $heading, $lines, $url, $button));
        } catch (\Throwable $e) {
            \Log::warning('B-Connect email failed for ' . $email . ': ' . $e->getMessage());
        }
    }

    public static function toUser(?User $user, string $subject, string $heading, array $lines, ?string $url = null, ?string $button = null): void
    {
        if (!$user || !$user->email) {
            return;
        }
        self::toEmail($user->email, $subject, $heading, $lines, $url, $button);
    }

    public static function toMember(?Member $member, string $subject, string $heading, array $lines, ?string $url = null, ?string $button = null): void
    {
        if (!$member) {
            return;
        }
        self::toUser($member->user, $subject, $heading, $lines, $url, $button);
    }

    public static function toCompanyAdmins(int $companyId, string $subject, string $heading, array $lines, ?string $url = null, ?string $button = null): void
    {
        $members = \App\Models\Bconnect\Member::with('user')
            ->where('company_id', $companyId)
            ->whereIn('role', ['company_admin', 'super_admin'])
            ->get();

        foreach ($members as $member) {
            self::toMember($member, $subject, $heading, $lines, $url, $button);
        }
    }

    public static function toCompanyMembers(int $companyId, string $subject, string $heading, array $lines, ?string $url = null, ?string $button = null): void
    {
        $members = \App\Models\Bconnect\Member::with('user')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->get();

        foreach ($members as $member) {
            self::toMember($member, $subject, $heading, $lines, $url, $button);
        }
    }
}
