<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskTimeLogController extends Controller
{
    public function store(Request $request, Project $project, Task $task): JsonResponse
    {
        $this->authorize('view', $project);

        $data = $request->validate(['seconds' => ['required', 'integer', 'min:1']]);

        $task->timeLogs()->create([
            'user_id' => $request->user()->id,
            'seconds' => $data['seconds'],
        ]);

        return response()->json([
            'time_seconds' => $task->timeLogs()->sum('seconds'),
        ]);
    }
}
