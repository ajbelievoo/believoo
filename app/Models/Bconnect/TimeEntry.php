<?php

namespace App\Models\Bconnect;

use Illuminate\Database\Eloquent\Model;

class TimeEntry extends Model
{
    protected $guarded = [];
    protected $table = 'bconnect_time_entries';

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'is_billable' => 'boolean',
        'hourly_rate' => 'decimal:2',
        'billed_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (TimeEntry $entry) {
            if ($entry->started_at && $entry->ended_at) {
                $entry->duration_seconds = $entry->started_at->diffInSeconds($entry->ended_at);
                if ($entry->is_billable && $entry->hourly_rate) {
                    $entry->billed_amount = round(($entry->duration_seconds / 3600) * $entry->hourly_rate, 2);
                }
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function getDurationHoursAttribute(): float
    {
        return round($this->duration_seconds / 3600, 2);
    }
}
