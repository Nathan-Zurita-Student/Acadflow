<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectWebhook;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function __construct(private WebhookService $webhooks) {}

    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $webhooks = $project->webhooks()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($w) => $this->resource($w));

        return response()->json($webhooks);
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $data = $request->validate([
            'url'    => ['required', 'url', 'max:500'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['string', 'in:task_created,task_updated,task_approved,task_rejected,member_added,meeting_scheduled'],
            'active' => ['boolean'],
        ]);

        $webhook = $project->webhooks()->create($data);

        return response()->json($this->resource($webhook), 201);
    }

    public function update(Request $request, Project $project, ProjectWebhook $webhook): JsonResponse
    {
        $this->authorize('update', $project);

        $data = $request->validate([
            'url'    => ['sometimes', 'url', 'max:500'],
            'events' => ['sometimes', 'array'],
            'events.*' => ['string', 'in:task_created,task_updated,task_approved,task_rejected,member_added,meeting_scheduled'],
            'active' => ['boolean'],
        ]);

        $webhook->update($data);

        return response()->json($this->resource($webhook));
    }

    public function destroy(Request $request, Project $project, ProjectWebhook $webhook): JsonResponse
    {
        $this->authorize('update', $project);
        $webhook->delete();

        return response()->json(null, 204);
    }

    public function test(Request $request, Project $project, ProjectWebhook $webhook): JsonResponse
    {
        $this->authorize('update', $project);

        $success = $this->webhooks->sendTest($webhook, $project, $request->user());

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Webhook enviado com sucesso!' : 'Falha ao enviar o webhook.',
        ]);
    }

    private function resource(ProjectWebhook $webhook): array
    {
        return [
            'id'         => $webhook->id,
            'url'        => $webhook->url,
            'events'     => $webhook->events,
            'active'     => $webhook->active,
            'created_at' => $webhook->created_at->toISOString(),
        ];
    }
}
