<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectInviteToken;
use App\Services\NotificationService;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InviteController extends Controller
{
    public function __construct(
        private NotificationService $notifications,
        private ProjectService $projectService,
    ) {}

    public function generate(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $data = $request->validate([
            'role' => ['required', 'in:member,leader'],
        ]);

        // Non-leaders can only generate member invite links
        $isLeader = $project->owner_id === $request->user()->id
            || $project->members()
                ->where('users.id', $request->user()->id)
                ->wherePivot('role', 'leader')
                ->exists();

        if ($data['role'] === 'leader' && ! $isLeader) {
            $data['role'] = 'member';
        }

        $invite = ProjectInviteToken::create([
            'project_id' => $project->id,
            'created_by' => $request->user()->id,
            'token'      => Str::random(48),
            'role'       => $data['role'],
            'expires_at' => now()->addDays(7),
        ]);

        return response()->json([
            'token'      => $invite->token,
            'role'       => $invite->role,
            'expires_at' => $invite->expires_at,
        ], 201);
    }

    public function info(string $token): JsonResponse
    {
        $invite = ProjectInviteToken::with('project')->where('token', $token)->first();

        if (! $invite || ! $invite->isValid()) {
            return response()->json(['message' => 'Link inválido ou expirado.'], 404);
        }

        return response()->json([
            'project' => [
                'id'           => $invite->project->id,
                'name'         => $invite->project->name,
                'description'  => $invite->project->description,
                'members_count' => $invite->project->members()->count(),
            ],
            'role'       => $invite->role,
            'expires_at' => $invite->expires_at,
        ]);
    }

    public function accept(Request $request, string $token): JsonResponse
    {
        $invite = ProjectInviteToken::with('project')->where('token', $token)->first();

        if (! $invite || ! $invite->isValid()) {
            return response()->json(['message' => 'Link inválido ou expirado.'], 404);
        }

        $project = $invite->project;
        $user    = $request->user();

        if ($project->members()->where('users.id', $user->id)->exists()) {
            return response()->json([
                'message'        => 'Você já é membro deste projeto.',
                'already_member' => true,
                'project_id'     => $project->id,
            ]);
        }

        $project->members()->attach($user->id, ['role' => $invite->role]);

        // Avisa o dono e quem gerou o link que um novo membro entrou
        $notifyIds = collect([$project->owner_id, $invite->created_by])
            ->unique()->filter(fn($id) => $id && $id !== $user->id);
        foreach ($notifyIds as $uid) {
            $this->notifications->notify($uid, 'project_member_joined', 'Novo membro no projeto',
                "{$user->name} entrou no projeto \"{$project->name}\".",
                ['project_id' => $project->id]);
        }

        $this->projectService->broadcastMembersChanged($project);
        $this->projectService->broadcastDashboardStale($project);

        return response()->json([
            'message'    => 'Bem-vindo ao projeto!',
            'project_id' => $project->id,
            'role'       => $invite->role,
        ]);
    }
}
