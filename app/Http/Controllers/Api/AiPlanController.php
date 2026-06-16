<?php

namespace App\Http\Controllers\Api;

use App\Events\TaskChanged;
use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Services\AiPlanService;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AiPlanController extends Controller
{
    public function __construct(
        private AiPlanService $aiPlan,
        private ProjectService $projectService,
    ) {}

    /** Gera um preview do plano (NÃO cria nada ainda). */
    public function generate(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $data = $request->validate([
            'content'  => ['nullable', 'string', 'max:50000'],
            'file'     => ['nullable', 'file', 'mimetypes:application/pdf', 'max:20480'],
            'due_date' => ['nullable', 'date'],
        ]);

        if (empty($data['content']) && ! $request->hasFile('file')) {
            return response()->json(['message' => 'Envie o enunciado em texto ou anexe um PDF.'], 422);
        }

        $pdfBase64 = null;
        if ($request->hasFile('file')) {
            $pdfBase64 = base64_encode(file_get_contents($request->file('file')->getRealPath()));
        }

        try {
            $tasks = $this->aiPlan->generatePlan(
                $project,
                $data['content'] ?? null,
                $pdfBase64,
                $data['due_date'] ?? null,
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['tasks' => $tasks]);
    }

    /** Cria de verdade as tarefas do plano (já revisado pelo usuário). */
    public function apply(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $columnKeys = $project->columns()->pluck('key')->all();

        $data = $request->validate([
            'tasks'                 => ['required', 'array', 'min:1', 'max:50'],
            'tasks.*.title'         => ['required', 'string', 'max:255'],
            'tasks.*.description'   => ['nullable', 'string'],
            'tasks.*.status'        => ['required', \Illuminate\Validation\Rule::in($columnKeys)],
            'tasks.*.priority'      => ['required', 'in:low,medium,high,urgent'],
            'tasks.*.due_date'      => ['nullable', 'date'],
            'tasks.*.assignee_id'   => ['nullable', 'integer', 'exists:users,id'],
            'tasks.*.subtasks'      => ['nullable', 'array'],
            'tasks.*.subtasks.*'    => ['string', 'max:255'],
        ]);

        $created = [];

        foreach ($data['tasks'] as $item) {
            $maxPos = $project->tasks()->where('status', $item['status'])->max('position') ?? -1;

            $task = $project->tasks()->create([
                'assignee_id' => $item['assignee_id'] ?? null,
                'created_by'  => $request->user()->id,
                'title'       => $item['title'],
                'description' => $item['description'] ?? null,
                'status'      => $item['status'],
                'priority'    => $item['priority'],
                'due_date'    => $item['due_date'] ?? null,
                'position'    => $maxPos + 1,
            ]);

            if (! empty($item['assignee_id'])) {
                $task->assignees()->sync([$item['assignee_id']]);
            }

            foreach (array_values($item['subtasks'] ?? []) as $i => $sub) {
                $task->checklists()->create(['title' => $sub, 'completed' => false, 'position' => $i]);
            }

            $task->load(['assignee', 'assignees', 'tags', 'checklists', 'timeLogs']);
            $resource = (new TaskResource($task))->resolve();

            try {
                broadcast(new TaskChanged($project->id, 'created', $resource));
            } catch (\Throwable $e) {
                Log::warning('TaskChanged broadcast failed: ' . $e->getMessage());
            }

            $created[] = $resource;
        }

        $project->recalculateProgress();
        $this->projectService->broadcastDashboardStale($project);
        $this->projectService->logActivity(
            $project,
            $request->user(),
            'created_task',
            'Project',
            $project->id,
            ['ai_plan' => count($created)],
        );

        return response()->json(['tasks' => $created], 201);
    }
}
