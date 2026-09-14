<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TeamInvitation extends Model
{
    protected $fillable = [
        'user_team_id',
        'invited_by',
        'email',
        'role',
        'permissions',
        'token',
        'status',
        'expires_at',
        'accepted_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invitation) {
            if (empty($invitation->token)) {
                $invitation->token = Str::random(60);
            }
            if (empty($invitation->expires_at)) {
                $invitation->expires_at = now()->addDays(7); // Expire in 7 days
            }
        });
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(UserTeam::class, 'user_team_id');
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' && !$this->isExpired();
    }

    public function accept()
    {
        $this->status = 'accepted';
        $this->accepted_at = now();
        $this->save();
    }

    public function decline()
    {
        $this->status = 'declined';
        $this->save();
    }

    public function getAcceptUrlAttribute(): string
    {
        return route('teams.invitations.accept', $this->token);
    }

    public function getDeclineUrlAttribute(): string
    {
        return route('teams.invitations.decline', $this->token);
    }
}
