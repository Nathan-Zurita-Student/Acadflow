<?php

namespace App\Notifications\Auth;

class PasswordResetCodeNotification extends CodeNotification
{
    protected function subjectLine(): string
    {
        return 'Código de recuperação de senha — AcadFlow';
    }

    protected function intro(): string
    {
        return 'Recebemos um pedido para redefinir sua senha. Use o código abaixo para continuar.';
    }

    protected function heading(): string
    {
        return 'Redefinição de senha';
    }
}
