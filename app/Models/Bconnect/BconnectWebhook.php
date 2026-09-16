<?php

namespace App\Models\Bconnect;

use Illuminate\Database\Eloquent\Model;

class BconnectWebhook extends Model
{
    protected $table = 'bconnect_webhooks';
    protected $guarded = [];
    protected $casts = ['events' => 'array', 'active' => 'boolean'];

    public function company() { return $this->belongsTo(Company::class, 'company_id'); }
    public function logs() { return $this->hasMany(BconnectWebhookLog::class, 'webhook_id'); }
}
