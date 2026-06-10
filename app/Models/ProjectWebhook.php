<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectWebhook extends Model
{
    protected $fillable = ['project_id', 'url', 'events', 'active'];

    protected function casts(): array
    {
        return [
            'events' => 'array',
            'active' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
