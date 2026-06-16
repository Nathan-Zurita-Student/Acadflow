<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectColumn extends Model
{
    protected $fillable = [
        'project_id',
        'key',
        'label',
        'color',
        'position',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    /**
     * Colunas padrão criadas para todo projeto novo.
     * O `key` é estável e usado por regras de negócio (ex.: 'done' = concluída),
     * por isso NÃO muda quando o usuário renomeia a coluna (só o label/cor mudam).
     */
    public const DEFAULTS = [
        ['key' => 'backlog',     'label' => 'Backlog',      'color' => 'text-slate-400'],
        ['key' => 'pending',     'label' => 'Pendente',     'color' => 'text-yellow-400'],
        ['key' => 'in_progress', 'label' => 'Em andamento', 'color' => 'text-blue-400'],
        ['key' => 'review',      'label' => 'Revisão',      'color' => 'text-purple-400'],
        ['key' => 'done',        'label' => 'Concluída',    'color' => 'text-emerald-400'],
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
