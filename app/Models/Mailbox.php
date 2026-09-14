<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mailbox extends Model
{
    protected $connection = 'mail';

    protected $table = 'mailbox';

    protected $primaryKey = 'username';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'username',
        'password',
        'password_encode',
        'full_name',
        'is_admin',
        'maildir',
        'quota',
        'current_usage',
        'quota_active',
        'local_part',
        'domain',
        'created',
        'modified',
        'active',
    ];

    protected $casts = [
        'quota' => 'integer',
        'current_usage' => 'integer',
        'is_admin' => 'boolean',
        'quota_active' => 'boolean',
        'active' => 'boolean',
    ];

    /**
     * Quota formatted in human readable form.
     */
    public function getQuotaFormattedAttribute(): string
    {
        if (!$this->quota) {
            return 'Unlimited';
        }

        $gb = $this->quota / 1073741824;

        return ($gb >= 1 ? rtrim(rtrim(number_format($gb, 1), '0'), '.') . ' GB' : round($this->quota / 1048576) . ' MB');
    }
}
