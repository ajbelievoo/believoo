<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class UserTeam extends Model
{
    protected $fillable = [
        'name',
        'description',
        'owner_id',
        'slug',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($team) {
            if (empty($team->slug)) {
                $team->slug = Str::slug($team->name);
            }
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function teamUsers(): HasMany
    {
        return $this->hasMany(TeamUser::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'team_users')
            ->withPivot('role', 'permissions')
            ->withTimestamps();
    }

    public function invitations()
    {
        return $this->hasMany(TeamInvitation::class, 'user_team_id');
    }

    public function hasUser(User $user): bool
    {
        return $this->users()->where('user_id', $user->id)->exists();
    }

    public function getUserRole(User $user): ?string
    {
        $teamUser = $this->teamUsers()->where('user_id', $user->id)->first();
        return $teamUser?->role;
    }

    public function getUserPermissions(User $user): array
    {
        $teamUser = $this->teamUsers()->where('user_id', $user->id)->first();
        return $teamUser?->permissions ?? [];
    }

    public function isTeamOwner(User $user): bool
    {
        return $this->owner_id === $user->id;
    }
}
