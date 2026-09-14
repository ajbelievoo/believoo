<?php
namespace App\Models\Bconnect;
use Illuminate\Database\Eloquent\Model;
class Project extends Model {
    protected $guarded = [];
    protected $table = 'bconnect_projects';
    public function company() { return $this->belongsTo(Company::class, 'company_id'); }
    public function client() { return $this->belongsTo(Member::class, 'client_id'); }
    public function tickets() { return $this->hasMany(Ticket::class, 'project_id'); }
    public function meetings() { return $this->hasMany(Meeting::class, 'project_id'); }
}
