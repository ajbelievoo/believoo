<?php

namespace App\Services;

use App\Models\Bconnect\Member;

class BconnectPermissionService
{
    public const PERMISSIONS = [
        'projects' => ['view', 'create', 'manage', 'delete'],
        'tickets' => ['view', 'create', 'manage', 'delete'],
        'sprints' => ['view', 'create', 'manage'],
        'time_tracking' => ['view', 'create', 'manage'],
        'billing' => ['view', 'manage'],
        'invoices' => ['view', 'create', 'manage'],
        'members' => ['view', 'manage'],
        'files' => ['view', 'upload', 'delete'],
        'meetings' => ['view', 'create', 'manage'],
        'reports' => ['view'],
        'remote' => ['use'],
        'whiteboard' => ['use'],
        'settings' => ['view', 'manage'],
        'chat' => ['use'],
    ];

    public static function defaultPermissions(string $role): array
    {
        return match ($role) {
            'super_admin', 'company_admin' => array_merge(...array_map(fn ($actions) => array_map(fn ($a) => $actions[0] . '.' . $a, $actions), self::PERMISSIONS)),
            'manager' => [
                'projects.view', 'projects.create', 'projects.manage',
                'tickets.view', 'tickets.create', 'tickets.manage', 'tickets.delete',
                'sprints.view', 'sprints.create', 'sprints.manage',
                'time_tracking.view', 'time_tracking.create', 'time_tracking.manage',
                'billing.view', 'invoices.view', 'invoices.create', 'invoices.manage',
                'members.view',
                'files.view', 'files.upload', 'files.delete',
                'meetings.view', 'meetings.create', 'meetings.manage',
                'reports.view',
                'remote.use',
                'whiteboard.use',
                'settings.view',
                'chat.use',
            ],
            'developer' => [
                'projects.view',
                'tickets.view', 'tickets.create', 'tickets.manage',
                'sprints.view',
                'time_tracking.view', 'time_tracking.create',
                'files.view', 'files.upload', 'files.delete',
                'meetings.view', 'meetings.create',
                'whiteboard.use',
                'chat.use',
            ],
            'client' => [
                'projects.view',
                'tickets.view', 'tickets.create',
                'files.view',
                'meetings.view', 'meetings.create',
                'chat.use',
                'settings.view',
                'billing.view', 'invoices.view',
                'whiteboard.use',
            ],
            default => [],
        };
    }

    public static function allowed(Member $member, string $permission): bool
    {
        if (in_array($member->role, ['super_admin', 'company_admin'], true)) return true;

        $memberPerms = $member->permissions ?: [];
        if (isset($memberPerms[$permission])) return (bool) $memberPerms[$permission];

        $defaults = self::defaultPermissions($member->role);
        return in_array($permission, $defaults, true);
    }

    public static function any(Member $member, array $permissions): bool
    {
        foreach ($permissions as $p) {
            if (self::allowed($member, $p)) return true;
        }
        return false;
    }

    public static function all(Member $member, array $permissions): bool
    {
        foreach ($permissions as $p) {
            if (!self::allowed($member, $p)) return false;
        }
        return true;
    }
}
