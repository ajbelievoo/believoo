<?php
namespace App\Models\Bconnect;
use Illuminate\Database\Eloquent\Model;
class Company extends Model {
    protected $guarded = [];
    protected $table = 'bconnect_companies';
    protected $casts = [
        'branding' => 'array',
        'is_active' => 'boolean',
        'plan_expires_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'grace_period_until' => 'datetime',
        'next_invoice_at' => 'datetime',
    ];
    public function members() { return $this->hasMany(Member::class, 'company_id'); }
    public function projects() { return $this->hasMany(Project::class, 'company_id'); }
    public function tickets() { return $this->hasMany(Ticket::class, 'company_id'); }
    public function invoices() { return $this->hasMany(Invoice::class, 'company_id'); }
    public function meetings() { return $this->hasMany(Meeting::class, 'company_id'); }
    public function auditLogs() { return $this->hasMany(AuditLog::class, 'company_id'); }
}
