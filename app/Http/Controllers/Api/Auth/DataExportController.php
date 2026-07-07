<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exportação de dados pessoais (LGPD, art. 18 — portabilidade).
 * Devolve um JSON legível por máquina com os dados do usuário autenticado.
 */
class DataExportController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $data = [
            'exportado_em' => now()->toIso8601String(),
            'aviso'        => 'Exportação de dados pessoais (LGPD) — AcadFlow.',
            'perfil' => [
                'id'                   => $user->id,
                'nome'                 => $user->name,
                'nome_exibicao'        => $user->display_name,
                'email'                => $user->email,
                'plano'                => $user->plan,
                'email_verificado_em'  => optional($user->email_verified_at)->toIso8601String(),
                'criado_em'            => optional($user->created_at)->toIso8601String(),
            ],
            'projetos_proprios' => $user->ownedProjects()
                ->get(['id', 'name', 'description', 'status', 'created_at'])
                ->map(fn ($p) => [
                    'id'        => $p->id,
                    'nome'      => $p->name,
                    'descricao' => $p->description,
                    'status'    => $p->status,
                    'criado_em' => optional($p->created_at)->toIso8601String(),
                ]),
            'participa_de' => $user->projects()
                ->get(['projects.id', 'projects.name'])
                ->map(fn ($p) => ['id' => $p->id, 'nome' => $p->name]),
            'tarefas_atribuidas' => Task::assignedTo($user->id)
                ->with('project:id,name')
                ->get()
                ->map(fn ($t) => [
                    'id'      => $t->id,
                    'titulo'  => $t->title,
                    'status'  => $t->status,
                    'prazo'   => optional($t->due_date)->toDateString(),
                    'projeto' => $t->project?->name,
                ]),
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $filename = 'acadflow-meus-dados-'.now()->format('Y-m-d').'.json';

        return response($json, 200, [
            'Content-Type'        => 'application/json; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
