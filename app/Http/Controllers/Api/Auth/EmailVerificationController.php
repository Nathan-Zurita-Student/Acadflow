<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\Auth\Concerns\RespondsWithUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CodeRequest;
use App\Services\Auth\EmailVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    use RespondsWithUser;

    public function __construct(private readonly EmailVerificationService $verification) {}

    /** Valida o código e marca o e-mail como verificado. */
    public function verify(CodeRequest $request): JsonResponse
    {
        $this->verification->verify($request->user(), $request->string('code')->value());

        return $this->userResponse($request->user()->fresh());
    }

    /** Gera e reenvia um novo código (invalida o anterior). */
    public function resend(Request $request): JsonResponse
    {
        $this->verification->send($request->user());

        return response()->json([
            'message' => 'Enviamos um novo código para o seu e-mail.',
        ]);
    }
}
