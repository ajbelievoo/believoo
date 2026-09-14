<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatAssignment extends Model
{
    const SLA_SECONDS = 120; // agent must first-reply within 2 minutes or flagged late

    protected $fillable = [
        'session_id',
        'agent_id',
        'status',
        'assigned_at',
        'first_reply_at',
        'first_reply_seconds',
        'closed_at',
        'closed_by',
        'closed_without_consent',
        'client_rating',
        'client_feedback',
        'summary',
        'sla_target_seconds',
        'sla_breached',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'first_reply_at' => 'datetime',
        'closed_at' => 'datetime',
        'closed_without_consent' => 'boolean',
    ];

    public function agent()
    {
        return $this->belongsTo(SupportAgent::class, 'agent_id');
    }

    /**
     * Record the agent's first reply and flag a late response (ZTP/SLA).
     */
    public function markFirstReply()
    {
        if ($this->first_reply_at) {
            return;
        }

        $seconds = max(0, (int) $this->assigned_at->diffInSeconds(now()));
        $this->update([
            'first_reply_at' => now(),
            'first_reply_seconds' => $seconds,
            'status' => 'active',
        ]);

        if ($seconds > self::SLA_SECONDS) {
            $this->agent()->increment('late_replies');
        }
    }
}
