<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskChecklistController extends Controller
{
    public function store(Request $request, Project $project, Task $task): JsonResponse
    {
        $this->authorize('view', $project);

        $data = $request->validate(['title' => ['required', 'string', 'max:255']]);
        $maxPos = $task->checklists()->max('position') ?? -1;

        $item = $task->checklists()->create([
            'title'    => $data['title'],
            'position' => $maxPos + 1,
        ]);

        return response()->json($item, 201);
    }

    public function update(Request $request, Project $project, Task $task, int $checklistId): JsonResponse
    {
        $this->authorize('view', $project);

        $item = $task->checklists()->findOrFail($checklistId);
        $data = $request->validate(['completed' => ['required', 'boolean']]);
        $item->update($data);

        return response()->json($item);
    }

    public function destroy(Request $request, Project $project, Task $task, int $checklistId): JsonResponse
    {
        $this->authorize('view', $project);
        $task->checklists()->where('id', $checklistId)->delete();

        return response()->json(null, 204);
    }
}
