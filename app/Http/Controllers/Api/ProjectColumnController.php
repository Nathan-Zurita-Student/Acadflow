<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectColumnController extends Controller
{
    /** Lista as colunas do Kanban do projeto. */
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        return response()->json($project->columns()->get()->map($this->resource(...)));
    }

    /** Cria uma coluna nova — recurso exclusivo de planos pagos (Pro/Ultra). */
    public function store(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);
        if ($denied = $this->ensurePro($project)) return $denied;

        $data = $request->validate([
            'label' => ['required', 'string', 'max:40'],
            'color' => ['nullable', 'string', 'max:40'],
        ]);

        $column = $project->columns()->create([
            'key'        => $this->uniqueKey($project, $data['label']),
            'label'      => $data['label'],
            'color'      => $data['color'] ?? 'text-slate-400',
            'position'   => (int) $project->columns()->max('position') + 1,
            'is_default' => false,
        ]);

        return response()->json($this->resource($column), 201);
    }

    /** Renomeia / recolore uma coluna (label e cor; o key permanece estável). */
    public function update(Request $request, Project $project, ProjectColumn $column): JsonResponse
    {
        $this->authorize('update', $project);
        if ($denied = $this->ensurePro($project)) return $denied;
        abort_unless($column->project_id === $project->id, 404);

        $data = $request->validate([
            'label' => ['sometimes', 'string', 'max:40'],
            'color' => ['sometimes', 'string', 'max:40'],
        ]);

        $column->update($data);

        return response()->json($this->resource($column));
    }

    /** Exclui uma coluna personalizada (as padrão não podem ser removidas). */
    public function destroy(Request $request, Project $project, ProjectColumn $column): JsonResponse
    {
        $this->authorize('update', $project);
        if ($denied = $this->ensurePro($project)) return $denied;
        abort_unless($column->project_id === $project->id, 404);

        if ($column->is_default) {
            return response()->json(['message' => 'Colunas padrão não podem ser excluídas.'], 422);
        }

        // Move as tarefas dessa coluna para a primeira coluna do projeto.
        $fallback = $project->columns()->where('id', '!=', $column->id)->orderBy('position')->first();
        if ($fallback) {
            $project->tasks()->where('status', $column->key)->update(['status' => $fallback->key]);
        }

        $column->delete();

        return response()->json(['message' => 'Coluna excluída.']);
    }

    /** Reordena as colunas conforme a lista de IDs recebida. */
    public function reorder(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);
        if ($denied = $this->ensurePro($project)) return $denied;

        $data = $request->validate([
            'ids'   => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        foreach ($data['ids'] as $position => $id) {
            $project->columns()->where('id', $id)->update(['position' => $position]);
        }

        return response()->json($project->columns()->get()->map($this->resource(...)));
    }

    /**
     * Personalizar o Kanban (renomear, recolorir, adicionar, reordenar, excluir)
     * é recurso dos planos pagos. Depende do plano do DONO do projeto.
     * Retorna uma resposta 403 quando bloqueado, ou null quando liberado.
     */
    private function ensurePro(Project $project): ?JsonResponse
    {
        if ($project->owner->isPro()) {
            return null;
        }

        return response()->json([
            'message' => 'Personalizar o Kanban é um recurso dos planos Pro e Ultra Pro.',
            'upgrade' => true,
        ], 403);
    }

    private function uniqueKey(Project $project, string $label): string
    {
        $base = Str::slug($label, '_') ?: 'coluna';
        $key = $base;
        $i = 1;
        while ($project->columns()->where('key', $key)->exists()) {
            $key = $base . '_' . (++$i);
        }

        return $key;
    }

    private function resource(ProjectColumn $column): array
    {
        return [
            'id'         => $column->id,
            'key'        => $column->key,
            'label'      => $column->label,
            'color'      => $column->color,
            'position'   => $column->position,
            'is_default' => $column->is_default,
        ];
    }
}
