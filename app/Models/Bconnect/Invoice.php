<?php
namespace App\Models\Bconnect;
use Illuminate\Database\Eloquent\Model;
class Invoice extends Model {
    protected $guarded = [];
    protected $table = 'bconnect_invoices';
    protected $casts = ['metadata' => 'array', 'paid_at' => 'datetime'];
    public function company() { return $this->belongsTo(Company::class, 'company_id'); }
    public function client() { return $this->belongsTo(Member::class, 'client_id'); }
}
