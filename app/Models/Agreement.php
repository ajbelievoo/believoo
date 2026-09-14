<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Agreement extends Model
{
    protected $fillable = [
        'agreement_number',
        'client_id',
        'title',
        'service_provider_name',
        'lead_developer',
        'client_name',
        'project_name',
        'project_overview',
        'technical_specs',
        'total_amount',
        'currency',
        'timeline_months',
        'start_date',
        'end_date',
        'upfront_amount',
        'payment_terms',
        'deliverables',
        'support_terms',
        'status',
        'sent_at',
        'signed_at',
        'client_signed_at',
        'admin_signature_data',
        'client_signature_data',
        'client_signature_ip',
        'client_signature_user_agent',
        'client_signature_certificate_id',
        'additional_terms',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'sent_at' => 'datetime',
        'signed_at' => 'datetime',
        'client_signed_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'upfront_amount' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function agreementRequest(): HasOne
    {
        return $this->hasOne(AgreementRequest::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class)->orderBy('sort_order');
    }

    public function amcSubscriptions(): HasMany
    {
        return $this->hasMany(AmcSubscription::class);
    }

    public function activeAmcSubscription(): ?AmcSubscription
    {
        return $this->amcSubscriptions()
            ->where('status', 'active')
            ->where('end_date', '>', now())
            ->first();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(AgreementInvoice::class)->latest();
    }

    public function workItems(): HasMany
    {
        return $this->hasMany(AgreementWorkItem::class)->orderBy('sort_order');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(AgreementMilestone::class)->orderBy('sort_order');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(AgreementHistory::class)->latest();
    }

    public function isSigned(): bool
    {
        return $this->status === 'signed';
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['sent', 'viewed']);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'sent' => 'blue',
            'viewed' => 'yellow',
            'signed' => 'green',
            'cancelled' => 'red',
            default => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'sent' => 'Sent to Client',
            'viewed' => 'Viewed by Client',
            'signed' => 'Signed & Active',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status),
        };
    }

    public function getFormattedTotalAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->total_amount, 2);
    }

    public function getTotalPaidAttribute(): float
    {
        return $this->milestones()->where('status', 'paid')->sum('payment_amount');
    }

    public function getRemainingAmountAttribute(): float
    {
        return $this->total_amount - $this->total_paid;
    }

    public function getProgressPercentageAttribute(): int
    {
        if ($this->milestones->isEmpty()) return 0;
        $completed = $this->milestones()->whereIn('status', ['completed', 'paid'])->count();
        return (int) round(($completed / $this->milestones->count()) * 100);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($agreement) {
            if (empty($agreement->agreement_number)) {
                $agreement->agreement_number = 'AGR-' . strtoupper(uniqid());
            }
        });
    }
}
