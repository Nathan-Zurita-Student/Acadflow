<?php

namespace App\Models;

class EmailChangeCode extends VerificationCode
{
    protected $table = 'email_change_codes';

    protected $fillable = [
        'user_id',
        'new_email',
        'code_hash',
        'attempts',
        'expires_at',
    ];
}
