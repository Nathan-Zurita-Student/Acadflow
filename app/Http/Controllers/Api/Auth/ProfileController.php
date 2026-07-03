<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\Auth\Concerns\RespondsWithUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ProfileUpdateRequest;
use App\Services\AvatarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    use RespondsWithUser;

    public function __construct(private readonly AvatarService $avatars) {}

    /** Estado do usuário autenticado (consumido pela SPA na inicialização). */
    public function me(Request $request): JsonResponse
    {
        return $this->userResponse($request->user());
    }

    /** Atualiza dados básicos do perfil (nome, display_name, avatar). */
    public function update(ProfileUpdateRequest $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->safe()->only(['name', 'display_name']);

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $this->avatars->replace($user->avatar, $request->file('avatar'));
        }

        $user->update($data);

        return $this->userResponse($user->fresh());
    }
}
