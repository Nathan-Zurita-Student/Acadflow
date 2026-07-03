<?php

namespace App\Models;

class PasswordResetCode extends VerificationCode
{
    protected $table = 'password_reset_codes';

    protected $fillable = [
        'user_id',
        'code_hash',
        'attempts',
        'expires_at',
    ];
}
