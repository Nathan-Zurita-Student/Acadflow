<?php

namespace App\Http\Controllers\Api\Auth\Concerns;

use App\Http\Resources\AuthUserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

trait RespondsWithUser
{
    /** Resposta JSON padrão dos endpoints de auth: { "user": {...} }. */
    protected function userResponse(User $user, int $status = 200): JsonResponse
    {
        return response()->json(['user' => AuthUserResource::make($user)], $status);
    }
}
