<?php

namespace App\Models\Bconnect;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $guarded = [];
    protected $table = 'bconnect_tickets';

    protected $casts = [
        'attachments' => 'array',
        'ai_tags' => 'array',
        'resolved_at' => 'datetime',
        'first_response_at' => 'datetime',
        'start_date' => 'date',
        'due_date' => 'date',
        'estimated_hours' => 'decimal:2',
        'sla_breached' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function sprint()
    {
        return $this->belongsTo(Sprint::class, 'sprint_id');
    }

    public function reporter()
    {
        return $this->belongsTo(Member::class, 'reporter_id');
    }

    public function assignee()
    {
        return $this->belongsTo(Member::class, 'assignee_id');
    }

    public function comments()
    {
        return $this->hasMany(TicketComment::class, 'ticket_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class, 'ticket_id');
    }

    public function slaPolicy()
    {
        return $this->belongsTo(BconnectSlaPolicy::class, 'sla_policy_id');
    }

    public function scopeKanban($query)
    {
        return $query->orderBy('position')->orderBy('updated_at', 'desc');
    }

    public function getTotalLoggedSecondsAttribute(): int
    {
        return $this->timeEntries()->sum('duration_seconds') ?? 0;
    }

    public function getTotalLoggedHoursAttribute(): float
    {
        return round($this->total_logged_seconds / 3600, 2);
    }

    public function getBillableAmountAttribute(): ?float
    {
        return $this->timeEntries()
            ->where('is_billable', true)
            ->sum('billed_amount') ?: null;
    }
}
