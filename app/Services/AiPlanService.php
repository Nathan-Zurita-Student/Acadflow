<?php

namespace App\Services;

use Anthropic\Client;
use App\Models\Project;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AiPlanService
{
    private const STATUSES   = ['backlog', 'pending', 'in_progress', 'review', 'done'];
    private const PRIORITIES = ['low', 'medium', 'high', 'urgent'];

    /**
     * Gera um plano de tarefas (preview) a partir do enunciado de um trabalho.
     *
     * @param  string|null  $text       Enunciado em texto (opcional se houver PDF)
     * @param  string|null  $pdfBase64  PDF do enunciado em base64 (opcional)
     * @param  string|null  $dueDate    Data de entrega do trabalho (YYYY-MM-DD)
     * @return array<int, array<string, mixed>>  Lista de tarefas normalizadas
     */
    public function generatePlan(Project $project, ?string $text, ?string $pdfBase64, ?string $dueDate): array
    {
        $apiKey = config('services.anthropic.api_key');
        if (empty($apiKey)) {
            throw new RuntimeException('A chave da IA não está configurada (ANTHROPIC_API_KEY).');
        }

        $project->loadMissing('members');
        $members = $project->members->map(fn($m) => ['id' => $m->id, 'name' => $m->name])->values()->all();

        $userBlocks = [];
        if ($pdfBase64) {
            $userBlocks[] = [
                'type'   => 'document',
                'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => $pdfBase64],
            ];
        }
        $userBlocks[] = ['type' => 'text', 'text' => $this->buildUserPrompt($project, $text, $dueDate, $members)];

        try {
            $client  = new Client(apiKey: $apiKey);
            $message = $client->messages->create(
                model: config('services.anthropic.model', 'claude-haiku-4-5'),
                maxTokens: 8192,
                system: $this->systemPrompt(),
                messages: [['role' => 'user', 'content' => $userBlocks]],
            );
        } catch (\Throwable $e) {
            $detail = $this->apiErrorMessage($e);
            Log::error('Anthropic generatePlan failed: ' . ($detail ?? $e->getMessage()));

            if ($detail && str_contains($detail, 'credit balance')) {
                throw new RuntimeException('A conta de IA está sem créditos. Adicione créditos em console.anthropic.com → Plans & Billing e tente novamente.');
            }
            throw new RuntimeException($detail ?: 'Não foi possível gerar o plano agora. Tente novamente em instantes.');
        }

        $raw = '';
        foreach ($message->content as $block) {
            if (($block->type ?? null) === 'text') {
                $raw .= $block->text;
            }
        }

        return $this->normalizeTasks($this->decodeJson($raw), $members);
    }

    /** Extrai a mensagem de erro legível que a API da Anthropic devolveu (se houver). */
    private function apiErrorMessage(\Throwable $e): ?string
    {
        if (! $e instanceof \Anthropic\Core\Exceptions\APIException || ! $e->response) {
            return null;
        }
        try {
            $body = $e->response->getBody();
            $body->rewind();
            $data = json_decode($body->getContents(), true);
            return $data['error']['message'] ?? null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function systemPrompt(): string
    {
        $statuses   = implode(', ', self::STATUSES);
        $priorities = implode(', ', self::PRIORITIES);

        return <<<TXT
        Você é um assistente que transforma o enunciado de um trabalho acadêmico em um plano de tarefas para um quadro Kanban de um grupo de estudantes.

        Quebre o trabalho em tarefas claras e acionáveis (geralmente entre 6 e 15), cada uma com subtarefas curtas (checklist). Distribua prazos realistas ao longo do tempo até a data de entrega. Escreva tudo em português do Brasil.

        Responda SOMENTE com um objeto JSON válido, sem nenhum texto antes ou depois e sem blocos de código markdown. O formato é:
        {
          "tasks": [
            {
              "title": "string curto",
              "description": "string (1-2 frases)",
              "status": "um de: {$statuses}",
              "priority": "um de: {$priorities}",
              "due_date": "YYYY-MM-DD ou null",
              "suggested_assignee_id": número (id de um membro fornecido) ou null,
              "subtasks": ["string", "..."]
            }
          ]
        }

        Regras:
        - A maioria das tarefas deve começar em "backlog" ou "pending".
        - "due_date" deve ser anterior ou igual à data de entrega informada; use null se não fizer sentido.
        - "suggested_assignee_id" é apenas uma sugestão (o líder fará a alocação depois). Use null quando não tiver certeza.
        - Não invente membros: use somente os ids da lista fornecida.
        TXT;
    }

    /** @param array<int, array{id:int,name:string}> $members */
    private function buildUserPrompt(Project $project, ?string $text, ?string $dueDate, array $members): string
    {
        $membersJson = json_encode($members, JSON_UNESCAPED_UNICODE);
        $deadline    = $dueDate ?: ($project->deadline?->toDateString() ?? 'não informada');
        $today       = now()->toDateString();
        $enunciado   = trim((string) $text) !== '' ? $text : '(veja o PDF anexado a esta mensagem)';

        return <<<TXT
        Projeto: {$project->name}
        Data de hoje: {$today}
        Data de entrega do trabalho: {$deadline}
        Membros do grupo (id e nome): {$membersJson}

        Enunciado do trabalho:
        {$enunciado}

        Gere o plano de tarefas em JSON conforme as instruções.
        TXT;
    }

    private function decodeJson(string $raw): array
    {
        $json = trim($raw);
        // Remove cercas de código markdown, se houver
        $json = preg_replace('/^```(?:json)?|```$/m', '', $json) ?? $json;
        // Recorta do primeiro { ao último }
        $start = strpos($json, '{');
        $end   = strrpos($json, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $json = substr($json, $start, $end - $start + 1);
        }

        $data = json_decode($json, true);
        if (! is_array($data) || ! isset($data['tasks']) || ! is_array($data['tasks'])) {
            throw new RuntimeException('A IA retornou um formato inesperado. Tente novamente.');
        }

        return $data;
    }

    /**
     * @param array<int, array{id:int,name:string}> $members
     * @return array<int, array<string, mixed>>
     */
    private function normalizeTasks(array $data, array $members): array
    {
        $memberIds = array_column($members, 'id');
        $tasks = [];

        foreach ($data['tasks'] as $t) {
            if (! is_array($t) || empty($t['title'])) {
                continue;
            }

            $status   = in_array($t['status'] ?? null, self::STATUSES, true) ? $t['status'] : 'backlog';
            $priority = in_array($t['priority'] ?? null, self::PRIORITIES, true) ? $t['priority'] : 'medium';

            $dueDate = null;
            if (! empty($t['due_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $t['due_date'])) {
                $dueDate = $t['due_date'];
            }

            $assignee = $t['suggested_assignee_id'] ?? null;
            $assignee = (is_int($assignee) || ctype_digit((string) $assignee)) && in_array((int) $assignee, $memberIds, true)
                ? (int) $assignee
                : null;

            $subtasks = [];
            if (! empty($t['subtasks']) && is_array($t['subtasks'])) {
                foreach ($t['subtasks'] as $s) {
                    $s = trim((string) $s);
                    if ($s !== '') $subtasks[] = mb_substr($s, 0, 255);
                }
            }

            $tasks[] = [
                'title'                 => mb_substr(trim((string) $t['title']), 0, 255),
                'description'           => isset($t['description']) ? trim((string) $t['description']) : '',
                'status'                => $status,
                'priority'              => $priority,
                'due_date'              => $dueDate,
                'suggested_assignee_id' => $assignee,
                'subtasks'              => array_slice($subtasks, 0, 20),
            ];
        }

        if (empty($tasks)) {
            throw new RuntimeException('A IA não conseguiu gerar tarefas a partir desse enunciado.');
        }

        return $tasks;
    }
}
