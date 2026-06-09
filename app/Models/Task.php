<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'assignee_id',
        'created_by',
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'position',
        'approval_status',
        'rejection_note',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'position' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'task_tags');
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(TaskChecklist::class)->orderBy('position');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->oldest();
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_assignees')->withTimestamps();
    }

    public function timeLogs(): HasMany
    {
        return $this->hasMany(TaskTimeLog::class);
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && $this->status !== 'done';
    }
}
