<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Busca global (Ctrl+K) — retorna projetos, tarefas e membros que o usuário
 * pode acessar (dono ou membro), filtrados pelo termo `q`. Escopo de
 * autorização espelha o do DashboardController.
 */
class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $q = trim((string) $request->query('q', ''));

        $empty = ['projects' => [], 'tasks' => [], 'members' => []];
        if ($q === '') {
            return response()->json($empty);
        }

        // Escapa curingas do LIKE para tratar o termo como texto literal.
        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $q).'%';

        $projectIds = Project::where('owner_id', $user->id)
            ->orWhereHas('members', fn ($m) => $m->where('users.id', $user->id))
            ->pluck('id');

        if ($projectIds->isEmpty()) {
            return response()->json($empty);
        }

        $projects = Project::whereIn('id', $projectIds)
            ->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('description', 'like', $like))
            ->orderBy('name')
            ->limit(6)
            ->get(['id', 'name', 'status'])
            ->map(fn ($p) => [
                'id'     => $p->id,
                'name'   => $p->name,
                'status' => $p->status,
            ]);

        $tasks = Task::whereIn('project_id', $projectIds)
            ->where('title', 'like', $like)
            ->with('project:id,name')
            ->latest('updated_at')
            ->limit(8)
            ->get()
            ->map(fn ($t) => [
                'id'       => $t->id,
                'title'    => $t->title,
                'status'   => $t->status,
                'priority' => $t->priority,
                'project'  => ['id' => $t->project->id, 'name' => $t->project->name],
            ]);

        $members = User::where(fn ($w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like))
            ->where(function ($scope) use ($projectIds) {
                $scope->whereHas('projects', fn ($s) => $s->whereIn('projects.id', $projectIds))
                      ->orWhereHas('ownedProjects', fn ($s) => $s->whereIn('id', $projectIds));
            })
            ->limit(6)
            ->get(['id', 'name', 'email', 'avatar'])
            ->map(function ($u) use ($projectIds) {
                // Um projeto compartilhado, para deep-link em /projects/{id}/members.
                $projectId = DB::table('project_members')
                    ->where('user_id', $u->id)
                    ->whereIn('project_id', $projectIds)
                    ->value('project_id')
                    ?? Project::whereIn('id', $projectIds)->where('owner_id', $u->id)->value('id');

                return [
                    'id'         => $u->id,
                    'name'       => $u->name,
                    'email'      => $u->email,
                    'avatar'     => $u->avatar,
                    'project_id' => $projectId,
                ];
            });

        return response()->json([
            'projects' => $projects,
            'tasks'    => $tasks,
            'members'  => $members->values(),
        ]);
    }
}
