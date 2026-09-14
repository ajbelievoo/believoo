<?php
namespace App\Models\Bconnect;
use Illuminate\Database\Eloquent\Model;
class AuditLog extends Model {
    protected $guarded = [];
    protected $table = 'bconnect_audit_logs';
    protected $casts = ['details' => 'array'];
    public function company() { return $this->belongsTo(Company::class, 'company_id'); }
    public function member() { return $this->belongsTo(Member::class, 'member_id'); }
}
