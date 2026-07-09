<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\ProjectInviteToken;
use App\Models\User;
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

    /**
     * Um token pode ser um link compartilhável (ProjectInviteToken) ou um convite
     * nominal enviado por e-mail (ProjectInvitation). Ambos abrem a mesma tela.
     */
    public function info(string $token): JsonResponse
    {
        if ($link = $this->findValidLink($token)) {
            return response()->json($this->invitePayload(
                $link->project,
                $link->role,
                $link->expires_at,
                $link->creator,
            ));
        }

        if ($invitation = $this->findValidInvitation($token)) {
            return response()->json($this->invitePayload(
                $invitation->project,
                $invitation->role,
                $invitation->expires_at,
                $invitation->invitedByUser,
            ));
        }

        return response()->json(['message' => 'Link inválido ou expirado.'], 404);
    }

    public function accept(Request $request, string $token): JsonResponse
    {
        if ($link = $this->findValidLink($token)) {
            return $this->join($request->user(), $link->project, $link->role, $link->created_by);
        }

        if ($invitation = $this->findValidInvitation($token)) {
            // Convite nominal: só a pessoa convidada pode usá-lo.
            if ($invitation->invited_user_id !== $request->user()->id) {
                return response()->json([
                    'message'       => 'Este convite foi enviado para outra conta.',
                    'wrong_account' => true,
                ], 403);
            }

            $invitation->update(['status' => ProjectInvitation::STATUS_ACCEPTED]);

            return $this->join(
                $request->user(),
                $invitation->project,
                $invitation->role,
                $invitation->invited_by_user_id,
            );
        }

        return response()->json(['message' => 'Link inválido ou expirado.'], 404);
    }

    private function findValidLink(string $token): ?ProjectInviteToken
    {
        $link = ProjectInviteToken::with(['project', 'creator'])->where('token', $token)->first();

        return $link && $link->isValid() ? $link : null;
    }

    private function findValidInvitation(string $token): ?ProjectInvitation
    {
        $invitation = ProjectInvitation::with(['project', 'invitedByUser'])->where('token', $token)->first();

        return $invitation && $invitation->isValid() ? $invitation : null;
    }

    /** @return array<string, mixed> */
    private function invitePayload(Project $project, string $role, $expiresAt, ?User $invitedBy): array
    {
        return [
            'project' => [
                'id'            => $project->id,
                'name'          => $project->name,
                'description'   => $project->description,
                'members_count' => $project->members()->count(),
            ],
            'role'       => $role,
            'expires_at' => $expiresAt,
            'invited_by' => $invitedBy ? [
                'name'   => $invitedBy->display_name ?: $invitedBy->name,
                'avatar' => $invitedBy->avatar,
            ] : null,
        ];
    }

    private function join(User $user, Project $project, string $role, ?int $invitedBy): JsonResponse
    {
        if ($project->members()->where('users.id', $user->id)->exists()) {
            return response()->json([
                'message'        => 'Você já é membro deste projeto.',
                'already_member' => true,
                'project_id'     => $project->id,
            ]);
        }

        $project->members()->attach($user->id, ['role' => $role]);

        // Avisa o dono e quem convidou que um novo membro entrou
        $notifyIds = collect([$project->owner_id, $invitedBy])
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
            'role'       => $role,
        ]);
    }
}
