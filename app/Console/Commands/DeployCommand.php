<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DeployCommand extends Command
{
    protected $signature = 'app:deploy';

    protected $description = 'Executa as tarefas pós-deploy (migrations, link de storage, caches).';

    public function handle(): int
    {
        // 1) Aplica as migrations pendentes.
        $this->step('Migrations', fn () => $this->call('migrate', ['--force' => true]));

        // 2) Cria o link público do storage — é isso que faz as fotos de perfil
        //    (e demais uploads) aparecerem. O link some a cada deploy, por isso
        //    precisa ser recriado sempre. --force recria mesmo se já existir.
        $this->step('Storage link', fn () => $this->call('storage:link', ['--force' => true]));

        // 3) Recria os caches de configuração e views (mais performance em produção).
        //    OBS: route:cache é intencionalmente omitido porque routes/web.php usa
        //    uma rota com closure (catch-all do Inertia), que não pode ser cacheada.
        $this->step('Config cache', fn () => $this->call('config:cache'));
        $this->step('View cache',   fn () => $this->call('view:cache'));

        // 4) Reinicia os workers de fila para que rodem o código novo.
        $this->step('Queue restart', fn () => $this->call('queue:restart'));

        $this->newLine();
        $this->info('✅ Deploy finalizado com sucesso.');

        return self::SUCCESS;
    }

    /** Executa um passo, mostrando o nome e seguindo mesmo que algo apenas avise. */
    private function step(string $name, callable $action): void
    {
        $this->newLine();
        $this->line("→ {$name}...");
        $action();
    }
}
