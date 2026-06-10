<?php

namespace App\Providers;

use App\Models\Project;
use App\Models\Task;
use App\Observers\TaskObserver;
use App\Policies\ProjectPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(Project::class, ProjectPolicy::class);
        Task::observe(TaskObserver::class);
    }
}
