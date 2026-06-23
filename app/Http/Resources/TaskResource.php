<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class TaskResource extends JsonResource
{
    /** Status agregado de um comentário para os checkmarks: sent | delivered | read. */
    protected function commentStatus($comment, Collection $linkedIds): string
    {
        $recipients = $linkedIds->reject(fn($id) => $id === $comment->user_id)->values();
        if ($recipients->isEmpty() || ! $comment->relationLoaded('reads')) return 'sent';

        $reads = $comment->reads;
        $readAll = $recipients->every(fn($rid) => optional($reads->firstWhere('user_id', $rid))->read_at !== null);
        if ($readAll) return 'read';

        $deliveredAll = $recipients->every(fn($rid) => optional($reads->firstWhere('user_id', $rid))->delivered_at !== null);
        if ($deliveredAll) return 'delivered';

        return 'sent';
    }

    public function toArray(Request $request): array
    {
        $assignees = $this->whenLoaded('assignees',
            fn() => $this->assignees->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'avatar' => $u->avatar]),
            collect(),
        );

        return [
            'id'               => $this->id,
            'title'            => $this->title,
            'description'      => $this->description,
            'status'           => $this->status,
            'priority'         => $this->priority,
            'due_date'         => $this->due_date?->toDateString(),
            'start_date'       => $this->start_date?->toDateString(),
            'position'         => $this->position,
            'is_overdue'       => $this->isOverdue(),
            'assignee'         => $this->whenLoaded('assignee', fn() => $this->assignee ? [
                'id'     => $this->assignee->id,
                'name'   => $this->assignee->name,
                'avatar' => $this->assignee->avatar,
            ] : null),
            'assignees'        => $assignees,
            'tags'             => $this->whenLoaded('tags',
                fn() => $this->tags->map(fn($t) => ['id' => $t->id, 'name' => $t->name, 'color' => $t->color]),
                collect(),
            ),
            'checklists_total' => $this->whenLoaded('checklists', fn() => $this->checklists->count(), 0),
            'checklists_done'  => $this->whenLoaded('checklists', fn() => $this->checklists->where('completed', true)->count(), 0),
            'time_seconds'     => $this->whenLoaded('timeLogs', fn() => $this->timeLogs->sum('seconds'), 0),
            'approval_status'  => $this->approval_status,
            'rejection_note'   => $this->rejection_note,
            'created_at'       => $this->created_at,
            'updated_at'       => $this->updated_at,

            // Detail fields — only included when relations are loaded
            'creator'     => $this->whenLoaded('creator', fn() => $this->creator ? [
                'id'   => $this->creator->id,
                'name' => $this->creator->name,
            ] : null),
            'checklists'  => $this->whenLoaded('checklists', fn() => $this->checklists->map(fn($c) => [
                'id'        => $c->id,
                'title'     => $c->title,
                'completed' => $c->completed,
                'position'  => $c->position,
            ])),
            'comments'    => $this->whenLoaded('comments', function () {
                $linkedIds = $this->relationLoaded('assignees')
                    ? $this->assignees->pluck('id')->push($this->created_by)->filter()->unique()
                    : collect();

                return $this->comments->map(fn($c) => [
                    'id'         => $c->id,
                    'content'    => $c->content,
                    'user'       => ['id' => $c->user->id, 'name' => $c->user->name, 'avatar' => $c->user->avatar],
                    'created_at' => $c->created_at,
                    'status'     => $this->commentStatus($c, $linkedIds),
                ]);
            }),
            'attachments' => $this->whenLoaded('attachments', fn() => $this->attachments->map(fn($a) => [
                'id'         => $a->id,
                'name'       => $a->name,
                'mime_type'  => $a->mime_type,
                'size'       => $a->size,
                'url'        => $a->url,
                'created_at' => $a->created_at,
            ])),
        ];
    }
}
