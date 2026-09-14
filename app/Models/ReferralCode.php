<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ReferralCode extends Model {
    protected $table = 'referral_codes';
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];
}
