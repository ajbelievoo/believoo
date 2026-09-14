<?php
namespace App\Models\Bconnect;
use Illuminate\Database\Eloquent\Model;
class Notification extends Model {
    protected $guarded = [];
    protected $table = 'bconnect_notifications';
    protected $casts = ['is_read' => 'boolean'];
    public function company() { return $this->belongsTo(Company::class, 'company_id'); }
    public function member() { return $this->belongsTo(Member::class, 'member_id'); }
}
