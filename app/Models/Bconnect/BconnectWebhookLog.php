<?php

namespace App\Models\Bconnect;

use Illuminate\Database\Eloquent\Model;

class BconnectWebhookLog extends Model
{
    protected $table = 'bconnect_webhook_logs';
    protected $guarded = [];
    protected $casts = ['delivered_at' => 'datetime'];

    public function webhook() { return $this->belongsTo(BconnectWebhook::class, 'webhook_id'); }
}
