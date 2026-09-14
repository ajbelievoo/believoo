<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgreementRequest extends Model
{
    protected $fillable = [
        'client_id',
        'request_number',
        'project_name',
        'project_description',
        'requirements',
        'budget_range',
        'timeline_expectation',
        'preferred_technology',
        'status',
        'agreement_id',
        'admin_notes',
        'submitted_at',
        'reviewed_at',
        'converted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'under_review' => 'info',
            'approved' => 'success',
            'rejected' => 'danger',
            'converted' => 'primary',
            default => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pending Review',
            'under_review' => 'Under Review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'converted' => 'Converted to Agreement',
            default => ucfirst($this->status),
        };
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isConverted(): bool
    {
        return $this->status === 'converted';
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($request) {
            if (empty($request->request_number)) {
                $request->request_number = 'REQ-' . strtoupper(uniqid());
            }
            if (empty($request->submitted_at) && $request->status === 'pending') {
                $request->submitted_at = now();
            }
        });
    }
}
