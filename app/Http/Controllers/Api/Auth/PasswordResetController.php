<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\Auth\PasswordResetService;
use Illuminate\Http\JsonResponse;

class PasswordResetController extends Controller
{
    public function __construct(private readonly PasswordResetService $service) {}

    /** Envia o código de recuperação. Resposta neutra (não enumera contas). */
    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        $this->service->sendCode($request->string('email')->value());

        return response()->json([
            'message' => 'Se o e-mail estiver cadastrado, você receberá um código de recuperação.',
        ]);
    }

    /** Redefine a senha com o código e encerra todas as sessões. */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $this->service->reset(
            $request->string('email')->value(),
            $request->string('code')->value(),
            $request->string('password')->value(),
        );

        return response()->json([
            'message' => 'Senha redefinida com sucesso. Faça login novamente.',
        ]);
    }
}
