<?php

namespace App\Providers;

use App\Listeners\Auth\AuthEventSubscriber;
use App\Models\Project;
use App\Models\Task;
use App\Observers\TaskObserver;
use App\Policies\ProjectPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(Project::class, ProjectPolicy::class);
        Task::observe(TaskObserver::class);

        Event::subscribe(AuthEventSubscriber::class);

        $this->configureRateLimiting();
    }

    /** Rate limiters de autenticação (login, cadastro e envio de códigos). */
    private function configureRateLimiting(): void
    {
        // Login: por (e-mail + IP) e um teto por IP, contra brute-force/enumeração.
        RateLimiter::for('login', function (Request $request) {
            $byCredentials = Str::lower((string) $request->input('email')).'|'.$request->ip();

            return [
                Limit::perMinute(5)->by($byCredentials),
                Limit::perMinute(20)->by($request->ip()),
            ];
        });

        // Cadastro: por IP.
        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        // Envio/reenvio de códigos: por usuário (ou IP) com teto por minuto e por hora.
        RateLimiter::for('codes', function (Request $request) {
            $key = (string) ($request->user()?->id ?: $request->ip());

            return [
                Limit::perMinute(3)->by($key),
                Limit::perHour(15)->by($key),
            ];
        });
    }
}
