<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'owner_id'    => User::factory(),
            'name'        => fake()->words(3, true),
            'description' => fake()->sentence(),
            'category'    => fake()->randomElement(['TCC', 'IC', 'Extensão', null]),
            'status'      => fake()->randomElement(['planning', 'active', 'paused']),
            'deadline'    => fake()->optional()->dateTimeBetween('now', '+6 months')?->format('Y-m-d'),
            'progress'    => fake()->numberBetween(0, 100),
        ];
    }
}
