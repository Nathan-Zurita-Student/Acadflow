<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectInvitationController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function store(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role'    => ['nullable', 'in:member,leader'],
        ]);

        $invitedUser = User::findOrFail($data['user_id']);

        if ($project->members()->where('user_id', $invitedUser->id)->exists()) {
            return response()->json(['message' => 'Usuário já é membro do projeto.'], 422);
        }

        $hasPending = ProjectInvitation::where('project_id', $project->id)
            ->where('invited_user_id', $invitedUser->id)
            ->where('status', ProjectInvitation::STATUS_PENDING)
            ->exists();

        if ($hasPending) {
            return response()->json(['message' => 'Já existe um convite pendente para este usuário.'], 422);
        }

        $invitation = ProjectInvitation::create([
            'project_id'         => $project->id,
            'invited_user_id'    => $invitedUser->id,
            'invited_by_user_id' => $request->user()->id,
            'role'               => $data['role'] ?? 'member',
            'status'             => ProjectInvitation::STATUS_PENDING,
            'expires_at'         => now()->addDays(7),
        ]);

        $this->notifications->notify(
            $invitedUser,
            'project_invitation',
            'Convite para projeto',
            "{$request->user()->name} convidou você para participar de \"{$project->name}\".",
            [
                'invitation_id' => $invitation->id,
                'project_id'    => $project->id,
                'project_name'  => $project->name,
                'role'          => $invitation->role,
                'invited_by'    => $request->user()->name,
            ]
        );

        return response()->json(['message' => 'Convite enviado com sucesso.'], 201);
    }

    public function respond(Request $request, ProjectInvitation $invitation): JsonResponse
    {
        if ($invitation->invited_user_id !== $request->user()->id) {
            return response()->json(['message' => 'Convite não encontrado.'], 403);
        }

        if (!$invitation->isValid()) {
            return response()->json(['message' => 'Este convite não é mais válido.'], 422);
        }

        $data = $request->validate([
            'action' => ['required', 'in:accept,decline'],
        ]);

        $invitation->project->loadMissing('members');

        if ($data['action'] === 'accept') {
            $invitation->update(['status' => ProjectInvitation::STATUS_ACCEPTED]);

            $invitation->project->members()->attach($invitation->invited_user_id, [
                'role' => $invitation->role,
            ]);

            $this->notifications->notify(
                $invitation->invited_by_user_id,
                'project_invitation_accepted',
                'Convite aceito',
                "{$request->user()->name} aceitou seu convite para o projeto \"{$invitation->project->name}\".",
                ['project_id' => $invitation->project_id]
            );
        } else {
            $invitation->update(['status' => ProjectInvitation::STATUS_DECLINED]);

            $this->notifications->notify(
                $invitation->invited_by_user_id,
                'project_invitation_declined',
                'Convite recusado',
                "{$request->user()->name} recusou seu convite para o projeto \"{$invitation->project->name}\".",
                ['project_id' => $invitation->project_id]
            );
        }

        return response()->json([
            'message' => $data['action'] === 'accept' ? 'Convite aceito.' : 'Convite recusado.',
        ]);
    }

    public function pending(Request $request): JsonResponse
    {
        $invitations = ProjectInvitation::with(['project', 'invitedByUser'])
            ->where('invited_user_id', $request->user()->id)
            ->where('status', ProjectInvitation::STATUS_PENDING)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->get();

        return response()->json($invitations->map(fn($inv) => [
            'id'         => $inv->id,
            'project'    => ['id' => $inv->project->id, 'name' => $inv->project->name],
            'role'       => $inv->role,
            'invited_by' => [
                'id'     => $inv->invitedByUser->id,
                'name'   => $inv->invitedByUser->name,
                'avatar' => $inv->invitedByUser->avatar,
            ],
            'expires_at' => $inv->expires_at,
            'created_at' => $inv->created_at,
        ]));
    }
}
