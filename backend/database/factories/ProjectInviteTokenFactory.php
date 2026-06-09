<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectInviteToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProjectInviteToken>
 */
class ProjectInviteTokenFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'created_by' => User::factory(),
            'token'      => Str::random(64),
            'role'       => 'member',
            'expires_at' => now()->addDays(7),
        ];
    }
}
