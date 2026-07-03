<?php

namespace App\Notifications\Auth;

class EmailChangeCodeNotification extends CodeNotification
{
    protected function subjectLine(): string
    {
        return 'Confirme seu novo e-mail — AcadFlow';
    }

    protected function intro(): string
    {
        return 'Use o código abaixo para confirmar a alteração do e-mail da sua conta.';
    }
}
