<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailDomain extends Model
{
    protected $connection = 'mail';

    protected $table = 'domain';

    protected $primaryKey = 'domain';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'domain',
        'a_record',
        'mailboxes',
        'mailbox_quota',
        'quota',
        'rate_limit',
        'created',
        'active',
    ];

    protected $casts = [
        'mailboxes' => 'integer',
        'active' => 'boolean',
    ];
}
