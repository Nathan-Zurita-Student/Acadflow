<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectInviteToken extends Model
{
    use HasFactory;

    protected $fillable = ['project_id', 'created_by', 'token', 'role', 'expires_at'];

    protected $casts = ['expires_at' => 'datetime'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isValid(): bool
    {
        return is_null($this->expires_at) || $this->expires_at->isFuture();
    }
}
