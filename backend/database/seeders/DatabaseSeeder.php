<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@acadflow.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $leader = User::create([
            'name' => 'Nathan Silva',
            'email' => 'nathan@acadflow.com',
            'password' => Hash::make('password'),
            'role' => 'leader',
        ]);

        $members = collect([
            ['name' => 'Maria Santos', 'email' => 'maria@acadflow.com'],
            ['name' => 'João Costa', 'email' => 'joao@acadflow.com'],
            ['name' => 'Ana Oliveira', 'email' => 'ana@acadflow.com'],
        ])->map(fn($d) => User::create(array_merge($d, [
            'password' => Hash::make('password'),
            'role' => 'member',
        ])));

        $project = Project::create([
            'owner_id' => $leader->id,
            'name' => 'Sistema de Gestão Acadêmica',
            'description' => 'Desenvolvimento do sistema completo de gestão para grupos de TCC.',
            'category' => 'TCC',
            'status' => 'active',
            'deadline' => now()->addMonths(3)->toDateString(),
        ]);

        $project->members()->attach($leader->id, ['role' => 'leader']);
        foreach ($members as $member) {
            $project->members()->attach($member->id, ['role' => 'member']);
        }

        $tags = collect(['Backend', 'Frontend', 'Design', 'Testes', 'Documentação'])->map(
            fn($name, $i) => Tag::create([
                'project_id' => $project->id,
                'name' => $name,
                'color' => ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'][$i],
            ])
        );

        $taskData = [
            ['title' => 'Configurar ambiente de desenvolvimento', 'status' => 'done', 'priority' => 'high', 'assignee' => $leader],
            ['title' => 'Modelagem do banco de dados', 'status' => 'done', 'priority' => 'high', 'assignee' => $leader],
            ['title' => 'Implementar autenticação JWT', 'status' => 'done', 'priority' => 'high', 'assignee' => $leader],
            ['title' => 'Criar API REST de projetos', 'status' => 'in_progress', 'priority' => 'high', 'assignee' => $leader],
            ['title' => 'Implementar sistema de tarefas', 'status' => 'in_progress', 'priority' => 'medium', 'assignee' => $members[0]],
            ['title' => 'Desenvolver dashboard Vue 3', 'status' => 'pending', 'priority' => 'high', 'assignee' => $members[0]],
            ['title' => 'Criar componentes Kanban', 'status' => 'pending', 'priority' => 'medium', 'assignee' => $members[1]],
            ['title' => 'Integração frontend/backend', 'status' => 'backlog', 'priority' => 'urgent', 'assignee' => $members[1]],
            ['title' => 'Testes de integração', 'status' => 'backlog', 'priority' => 'medium', 'assignee' => $members[2]],
            ['title' => 'Documentação da API', 'status' => 'backlog', 'priority' => 'low', 'assignee' => $members[2]],
            ['title' => 'Deploy em produção', 'status' => 'backlog', 'priority' => 'urgent', 'assignee' => $leader],
        ];

        foreach ($taskData as $i => $d) {
            $task = Task::create([
                'project_id' => $project->id,
                'assignee_id' => $d['assignee']->id,
                'created_by' => $leader->id,
                'title' => $d['title'],
                'status' => $d['status'],
                'priority' => $d['priority'],
                'due_date' => now()->addDays(rand(3, 30))->toDateString(),
                'position' => $i,
            ]);

            $task->tags()->attach($tags->random(rand(1, 2))->pluck('id'));

            TaskComment::create([
                'task_id' => $task->id,
                'user_id' => $leader->id,
                'content' => "Tarefa criada e atribuída para revisão.",
            ]);
        }

        $project->recalculateProgress();
    }
}
