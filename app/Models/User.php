<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'display_name',
        'email',
        'password',
        'avatar',
        'role',
        'google_id',
        'plan',
        'plan_status',
        'plan_expires_at',
        'asaas_customer_id',
        'asaas_subscription_id',
        'cpf_cnpj',
        'ai_usage_count',
        'ai_usage_period',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'plan_expires_at'   => 'datetime',
            'password'          => 'hashed',
        ];
    }

    /**
     * Plano efetivo do usuário. Se a assinatura paga venceu (sem renovar
     * dentro da tolerância), cai automaticamente para "free".
     */
    public function effectivePlan(): string
    {
        $plan = $this->plan ?: 'free';

        if ($plan === 'free') {
            return 'free';
        }

        // Ainda não pagou (assinatura criada mas sem confirmação) ou foi
        // estornada/removida → sem acesso ao plano pago.
        if ($this->plan_status === 'pending' || $this->plan_status === 'inactive') {
            return 'free';
        }

        // Para 'active', 'overdue' e 'canceled' o acesso vale até a data que já
        // foi paga (plan_expires_at). Ao cancelar, o usuário NÃO perde o plano na
        // hora: continua usando até o fim do período que pagou.
        if (! $this->plan_expires_at || $this->plan_expires_at->isPast()) {
            return 'free';
        }

        return $plan;
    }

    public function isPro(): bool
    {
        return in_array($this->effectivePlan(), ['pro', 'ultra'], true);
    }

    public function isUltra(): bool
    {
        return $this->effectivePlan() === 'ultra';
    }

    /** Limites configurados (config/plans.php) para o plano efetivo. */
    public function planLimits(): array
    {
        $plan = $this->effectivePlan();

        return config("plans.plans.{$plan}.limits", config('plans.plans.free.limits'));
    }

    /**
     * Retorna o limite de um recurso para o plano atual.
     * `null` significa ilimitado.
     */
    public function limitFor(string $key): ?int
    {
        return $this->planLimits()[$key] ?? null;
    }

    /** Verifica se ainda está dentro do limite de um recurso. */
    public function withinLimit(string $key, int $current): bool
    {
        $limit = $this->limitFor($key);

        return $limit === null || $current < $limit;
    }

    public function ownedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'owner_id');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assignee_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }
}
