<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignRecipient extends Model
{
    protected $fillable = [
        'campaign_id',
        'recipient_type',
        'recipient_id',
        'email',
        'status',
        'sent_at',
        'opened_at',
        'clicked_at',
        'open_token',
        'click_token',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'opened_at' => 'datetime',
            'clicked_at' => 'datetime',
        ];
    }

    public function campaign()
    {
        return $this->belongsTo(EmailCampaign::class, 'campaign_id');
    }

    public function recipient()
    {
        return $this->morphTo();
    }

    protected static function booted(): void
    {
        static::creating(function (CampaignRecipient $r) {
            if (empty($r->open_token)) {
                $r->open_token = hash('sha256', uniqid('open_', true));
            }
            if (empty($r->click_token)) {
                $r->click_token = hash('sha256', uniqid('click_', true));
            }
        });
    }
}
