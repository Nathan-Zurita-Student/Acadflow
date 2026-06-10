<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $notes = $project->notes()
            ->with('author:id,name,avatar')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(fn($n) => $this->resource($n));

        return response()->json($notes);
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $data = $request->validate([
            'title'   => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
        ]);

        $note = $project->notes()->create([
            ...$data,
            'user_id' => $request->user()->id,
        ]);

        $note->load('author:id,name,avatar');

        return response()->json($this->resource($note), 201);
    }

    public function update(Request $request, Project $project, ProjectNote $note): JsonResponse
    {
        $this->authorize('view', $project);

        $data = $request->validate([
            'title'   => ['sometimes', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
        ]);

        $note->update($data);
        $note->load('author:id,name,avatar');

        return response()->json($this->resource($note));
    }

    public function destroy(Request $request, Project $project, ProjectNote $note): JsonResponse
    {
        $this->authorize('view', $project);

        if ($note->user_id !== $request->user()->id) {
            $this->authorize('update', $project);
        }

        $note->delete();

        return response()->json(null, 204);
    }

    private function resource(ProjectNote $note): array
    {
        return [
            'id'         => $note->id,
            'title'      => $note->title,
            'content'    => $note->content,
            'author'     => $note->author ? [
                'id'     => $note->author->id,
                'name'   => $note->author->name,
                'avatar' => $note->author->avatar,
            ] : null,
            'created_at' => $note->created_at->toISOString(),
            'updated_at' => $note->updated_at->toISOString(),
        ];
    }
}
