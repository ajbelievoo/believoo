<?php
namespace App\Models\Bconnect;
use Illuminate\Database\Eloquent\Model;
class Member extends Model {
    protected $guarded = [];
    protected $table = 'bconnect_members';
    protected $casts = ['permissions' => 'array', 'is_active' => 'boolean'];
    public function user() { return $this->belongsTo(\App\Models\User::class); }
    public function company() { return $this->belongsTo(Company::class, 'company_id'); }
}
