<?php

namespace App\Services;

use App\Models\Bconnect\Member;
use App\Models\Bconnect\Notification;

class BconnectNotificationService
{
    public static function send(Member|int $member, string $type, string $title, string $message, ?string $url = null, ?int $companyId = null): void
    {
        $memberId = $member instanceof Member ? $member->id : $member;
        $companyId ??= $member instanceof Member ? $member->company_id : null;

        if (!$companyId || !$memberId) return;

        Notification::create([
            'company_id' => $companyId,
            'member_id' => $memberId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'url' => $url,
            'is_read' => false,
        ]);
    }

    public static function sendToCompanyAdmins(int $companyId, string $type, string $title, string $message, ?string $url = null): void
    {
        $admins = Member::where('company_id', $companyId)
            ->whereIn('role', ['company_admin', 'super_admin'])
            ->where('is_active', true)
            ->get();

        foreach ($admins as $admin) {
            self::send($admin, $type, $title, $message, $url, $companyId);
        }
    }

    public static function sendToMembers(array $memberIds, int $companyId, string $type, string $title, string $message, ?string $url = null): void
    {
        foreach (array_unique($memberIds) as $id) {
            if (!$id) continue;
            self::send($id, $type, $title, $message, $url, $companyId);
        }
    }
}
