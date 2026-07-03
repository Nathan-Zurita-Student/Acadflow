<?php

namespace App\Http\Controllers\Api\Auth;

use App\Events\Auth\PasswordChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Notifications\Auth\PasswordChangedNotification;
use App\Services\Auth\SessionService;
use Illuminate\Http\JsonResponse;

class PasswordController extends Controller
{
    public function __construct(private readonly SessionService $sessions) {}

    /** Altera a senha (com senha atual), mantém a sessão atual e derruba as outras. */
    public function update(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->forceFill(['password' => $request->string('password')->value()])->save();

        // Mantém a sessão atual válida caso o AuthenticateSession esteja ativo
        // (ele compara o hash da senha guardado na sessão).
        $request->session()->put('password_hash_web', $user->getAuthPassword());

        // Segurança: encerra as demais sessões e revoga tokens de API.
        $this->sessions->destroyOthers($user, $request->session()->getId());
        $user->tokens()->delete();

        $user->notify(new PasswordChangedNotification());
        event(new PasswordChanged($user));

        return response()->json(['message' => 'Senha alterada com sucesso.']);
    }
}
