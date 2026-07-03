<?php

namespace App\Notifications\Auth;

class EmailVerificationCodeNotification extends CodeNotification
{
    protected function subjectLine(): string
    {
        return 'Confirme seu e-mail — AcadFlow';
    }

    protected function intro(): string
    {
        return 'Use o código abaixo para confirmar seu endereço de e-mail e ativar sua conta.';
    }
}
