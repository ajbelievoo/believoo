<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamUser extends Model
{
    protected $fillable = [
        'user_team_id',
        'user_id',
        'role',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    public function userTeam(): BelongsTo
    {
        return $this->belongsTo(UserTeam::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->role === 'owner' || $this->role === 'admin') {
            return true;
        }

        // Developers have access to dashboard, hosting, and reports
        if ($this->role === 'developer') {
            $developerPermissions = ['view_dashboard', 'manage_hosting', 'view_reports'];
            return in_array($permission, $developerPermissions) || in_array($permission, $this->permissions ?? []);
        }

        return in_array($permission, $this->permissions ?? []);
    }

    public const ROLES = [
        'owner' => 'Owner',
        'admin' => 'Admin',
        'developer' => 'Developer',
        'member' => 'Member',
    ];

    public const PERMISSIONS = [
        'view_dashboard' => 'View Dashboard',
        'manage_orders' => 'Manage Orders',
        'manage_hosting' => 'Manage Hosting',
        'manage_team' => 'Manage Team',
        'view_reports' => 'View Reports',
        'manage_billing' => 'Manage Billing',
    ];
}
