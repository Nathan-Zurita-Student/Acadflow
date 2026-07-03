<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\Auth\Concerns\RespondsWithUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    use RespondsWithUser;

    /** Login por sessão (cookie). Regenera o Session ID e registra o acesso. */
    public function store(LoginRequest $request): JsonResponse
    {
        $credentials = [
            'email'    => $request->string('email')->value(),
            'password' => $request->string('password')->value(),
        ];

        // Auth::attempt dispara o evento Failed (auditoria) em caso de erro e
        // ignora usuários com soft delete (global scope). Mensagem genérica:
        // nunca revela se o e-mail existe.
        if (! Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas.'],
            ]);
        }

        $request->session()->regenerate();

        $user = $request->user();
        $user->recordLogin($request->ip());

        return $this->userResponse($user);
    }

    /** Logout: encerra a sessão atual e invalida o token CSRF. */
    public function destroy(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logout realizado com sucesso.']);
    }
}
