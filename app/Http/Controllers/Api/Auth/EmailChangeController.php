<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\Auth\Concerns\RespondsWithUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangeEmailRequest;
use App\Http\Requests\Auth\CodeRequest;
use App\Services\Auth\EmailChangeService;
use Illuminate\Http\JsonResponse;

class EmailChangeController extends Controller
{
    use RespondsWithUser;

    public function __construct(private readonly EmailChangeService $service) {}

    /** Passo 1: valida senha + novo e-mail e envia código ao novo endereço. */
    public function request(ChangeEmailRequest $request): JsonResponse
    {
        $this->service->requestChange($request->user(), $request->string('email')->value());

        return response()->json([
            'message' => 'Enviamos um código de confirmação para o novo e-mail.',
        ]);
    }

    /** Passo 2: confirma o código, troca o e-mail e avisa o endereço antigo. */
    public function confirm(CodeRequest $request): JsonResponse
    {
        $this->service->confirm($request->user(), $request->string('code')->value());

        return $this->userResponse($request->user()->fresh());
    }
}
