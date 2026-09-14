<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTask extends Model
{
    protected $fillable = [
        'agreement_id',
        'milestone_id',
        'task_name',
        'description',
        'status',
        'priority',
        'progress_percentage',
        'assigned_to',
        'start_date',
        'due_date',
        'completed_at',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'completed_at' => 'date',
    ];

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(AgreementMilestone::class);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'gray',
            'in_progress' => 'blue',
            'completed' => 'green',
            'on_hold' => 'yellow',
            default => 'gray',
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'low' => 'gray',
            'medium' => 'yellow',
            'high' => 'red',
            default => 'gray',
        };
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && $this->status !== 'completed';
    }
}
