<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'title',
        'title_b',
        'message',
        'message_b',
        'message_hi',
        'message_hi_b',
        'type',
        'locale',
        'audience',
        'segment',
        'ab_test_name',
        'ab_enabled',
        'ab_test_percentage',
        'ab_split',
        'ab_metric',
        'ab_duration_minutes',
        'ab_status',
        'ab_winner',
        'ab_winner_sent_at',
        'ab_test_started_at',
        'variant',
        'throttle_per_minute',
        'send_sms',
        'send_push',
        'attachment',
        'is_published',
        'sent_at',
        'scheduled_at',
        'sent_by',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'send_sms' => 'boolean',
        'send_push' => 'boolean',
        'ab_enabled' => 'boolean',
        'ab_test_percentage' => 'integer',
        'ab_split' => 'integer',
        'ab_duration_minutes' => 'integer',
        'sent_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'ab_winner_sent_at' => 'datetime',
        'ab_test_started_at' => 'datetime',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function template()
    {
        return $this->belongsTo(AnnouncementTemplate::class);
    }

    public function recipients()
    {
        return $this->hasMany(AnnouncementRecipient::class);
    }

    public function logs()
    {
        return $this->hasMany(AnnouncementLog::class);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeUnsent($query)
    {
        return $query->whereNull('sent_at');
    }

    public function scopeScheduled($query)
    {
        return $query->whereNotNull('scheduled_at')->whereNull('sent_at');
    }

    public function isScheduled(): bool
    {
        return ! $this->sent_at && $this->scheduled_at && $this->scheduled_at->isFuture();
    }

    public function messageForLocale(string $locale): ?string
    {
        if ($locale === 'hi' && $this->message_hi) {
            return $this->message_hi;
        }
        return $this->message;
    }

    public function titleForVariant(string $variant = 'A'): string
    {
        if ($variant === 'B' && $this->ab_enabled && $this->title_b) {
            return $this->title_b;
        }

        return $this->title;
    }

    public function messageForLocaleAndVariant(string $locale = 'en', string $variant = 'A'): ?string
    {
        if ($variant === 'B' && $this->ab_enabled) {
            if ($locale === 'hi' && $this->message_hi_b) {
                return $this->message_hi_b;
            }

            return $this->message_b ?? $this->message;
        }

        return $this->messageForLocale($locale);
    }

    public function abDurationIsOver(): bool
    {
        return $this->ab_test_started_at
            && $this->ab_test_started_at->addMinutes($this->ab_duration_minutes)->isPast();
    }

    public function isAbTesting(): bool
    {
        return $this->ab_enabled && $this->ab_status === 'testing';
    }
}
