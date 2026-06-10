<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Meeting\StoreMeetingRequest;
use App\Http\Requests\Meeting\UpdateMeetingRequest;
use App\Models\Meeting;
use App\Models\Project;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeetingController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $meetings = $project->meetings()
            ->with('creator:id,name,avatar')
            ->orderBy('scheduled_at', 'asc')
            ->get()
            ->map(fn($m) => $this->resource($m));

        return response()->json($meetings);
    }

    public function store(StoreMeetingRequest $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $data = $request->validated();

        $meeting = $project->meetings()->create([
            ...$data,
            'created_by' => $request->user()->id,
        ]);

        $meeting->load('creator:id,name,avatar');

        // Notify all project members about the new meeting
        $members = $project->members()
            ->where('users.id', '!=', $request->user()->id)
            ->get();

        if ($members->isNotEmpty()) {
            $this->notifications->notifyMany(
                $members,
                'meeting_scheduled',
                'Nova reunião agendada 📅',
                "{$request->user()->name} agendou \"{$meeting->title}\" para " .
                    $meeting->scheduled_at->format('d/m/Y H:i') . '.',
                ['project_id' => $project->id, 'meeting_id' => $meeting->id],
            );
        }

        return response()->json($this->resource($meeting), 201);
    }

    public function update(UpdateMeetingRequest $request, Project $project, Meeting $meeting): JsonResponse
    {
        $this->authorize('view', $project);

        $data = $request->validated();

        $meeting->update($data);
        $meeting->load('creator:id,name,avatar');

        return response()->json($this->resource($meeting));
    }

    public function destroy(Request $request, Project $project, Meeting $meeting): JsonResponse
    {
        $this->authorize('view', $project);

        if ($meeting->created_by !== $request->user()->id) {
            $this->authorize('update', $project);
        }

        $meeting->delete();

        return response()->json(null, 204);
    }

    private function resource(Meeting $meeting): array
    {
        return [
            'id'           => $meeting->id,
            'title'        => $meeting->title,
            'description'  => $meeting->description,
            'scheduled_at' => $meeting->scheduled_at?->toISOString(),
            'location'     => $meeting->location,
            'notes'        => $meeting->notes,
            'creator'      => $meeting->creator ? [
                'id'     => $meeting->creator->id,
                'name'   => $meeting->creator->name,
                'avatar' => $meeting->creator->avatar,
            ] : null,
            'is_past'      => $meeting->scheduled_at?->isPast() ?? false,
            'created_at'   => $meeting->created_at->toISOString(),
        ];
    }
}
