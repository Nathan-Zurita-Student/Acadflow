<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectWebhook;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    public function dispatch(Project $project, string $event, array $payload): void
    {
        $webhooks = $project->webhooks()
            ->where('active', true)
            ->get()
            ->filter(fn($w) => in_array($event, $w->events));

        foreach ($webhooks as $webhook) {
            $this->send($webhook->url, $event, $payload, $project);
        }
    }

    public function sendTest(ProjectWebhook $webhook, Project $project, User $sender): bool
    {
        return $this->send($webhook->url, 'test', [
            'message' => 'Teste de webhook do AcadFlow',
            'sent_by' => $sender->name,
        ], $project);
    }

    private function send(string $url, string $event, array $payload, Project $project): bool
    {
        try {
            Http::timeout(5)->post($url, [
                'event'      => $event,
                'project_id' => $project->id,
                'project'    => $project->name,
                'data'       => $payload,
                'timestamp'  => now()->toISOString(),
            ]);
            return true;
        } catch (\Throwable $e) {
            Log::warning("Webhook delivery failed for project {$project->id}: {$e->getMessage()}");
            return false;
        }
    }
}
