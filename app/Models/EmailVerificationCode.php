<?php

namespace App\Models;

class EmailVerificationCode extends VerificationCode
{
    protected $table = 'email_verification_codes';

    protected $fillable = [
        'user_id',
        'code_hash',
        'attempts',
        'expires_at',
    ];
}
