<?php

namespace App\Models\Bconnect;

use Illuminate\Database\Eloquent\Model;

class BconnectSlaPolicy extends Model
{
    protected $table = 'bconnect_sla_policies';
    protected $guarded = [];
    protected $casts = ['conditions' => 'array', 'active' => 'boolean'];

    public function company() { return $this->belongsTo(Company::class, 'company_id'); }
    public function tickets() { return $this->hasMany(Ticket::class, 'sla_policy_id'); }
}
