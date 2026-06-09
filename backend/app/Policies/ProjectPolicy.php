<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        return $project->owner_id === $user->id
            || $project->members()->where('users.id', $user->id)->exists();
    }

    public function update(User $user, Project $project): bool
    {
        if ($project->owner_id === $user->id) return true;

        return $project->members()
            ->where('users.id', $user->id)
            ->wherePivot('role', 'leader')
            ->exists();
    }

    public function delete(User $user, Project $project): bool
    {
        return $project->owner_id === $user->id || $user->role === 'admin';
    }
}
