<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportAgent extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'display_name',
        'email',
        'password',
        'phone',
        'avatar',
        'is_active',
        'is_online',
        'max_chats',
        'active_chats',
        'last_seen_at',
        'total_chats',
        'late_replies',
        'unpermitted_closes',
        'avg_rating',
        'rating_count',
        'canned_replies',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_online' => 'boolean',
        'last_seen_at' => 'datetime',
        'password' => 'hashed',
        'canned_replies' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignments()
    {
        return $this->hasMany(ChatAssignment::class, 'agent_id');
    }

    /**
     * Find a free agent for a new live chat.
     */
    public static function findFree()
    {
        // Round-robin: get agent who was assigned longest ago (feature 20)
        return static::where('is_active', true)
            ->where('is_online', true)
            ->whereColumn('active_chats', '<', 'max_chats')
            ->orderBy('last_assigned_at', 'asc') // round-robin
            ->orderBy('active_chats', 'asc')
            ->orderBy('last_seen_at', 'desc')
            ->first();
    }

    /**
     * An agent is considered offline if they have not been seen recently.
     */
    public static function pruneStaleOnline($minutes = 5)
    {
        static::where('is_online', true)
            ->where(function ($q) use ($minutes) {
                $q->whereNull('last_seen_at')
                  ->orWhere('last_seen_at', '<', now()->subMinutes($minutes));
            })
            ->update(['is_online' => false]);
    }
}
