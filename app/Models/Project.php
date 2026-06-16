<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'name',
        'description',
        'category',
        'status',
        'deadline',
        'progress',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'progress' => 'integer',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function columns(): HasMany
    {
        return $this->hasMany(ProjectColumn::class)->orderBy('position');
    }

    /**
     * Cria as colunas padrão do Kanban para este projeto.
     * Fonte única usada na criação de projeto (ProjectService), no seeder e nas
     * factories — todo projeto SEMPRE nasce com as colunas, e o status de tarefa
     * é validado contra elas.
     */
    public function seedDefaultColumns(): void
    {
        foreach (ProjectColumn::DEFAULTS as $pos => $col) {
            $this->columns()->create([
                'key'        => $col['key'],
                'label'      => $col['label'],
                'color'      => $col['color'],
                'position'   => $pos,
                'is_default' => true,
            ]);
        }
    }

    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ProjectNote::class);
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(ProjectWebhook::class);
    }

    public function recalculateProgress(): void
    {
        $total = $this->tasks()->count();
        if ($total === 0) {
            $this->update(['progress' => 0]);
            return;
        }
        $done = $this->tasks()->where('status', 'done')->count();
        $this->update(['progress' => (int) round(($done / $total) * 100)]);
    }
}
