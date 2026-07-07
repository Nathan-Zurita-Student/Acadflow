<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Exclusão definitiva de contas com soft delete além da carência (30 dias).
Schedule::command('auth:purge-deleted')->daily();

// Lembrete de tarefas que vencem amanhã (e-mail aos responsáveis).
Schedule::command('tasks:notify-due')->dailyAt('08:00');

// Resumo semanal por e-mail (segunda-feira de manhã).
Schedule::command('reports:weekly')->weeklyOn(1, '08:00');
