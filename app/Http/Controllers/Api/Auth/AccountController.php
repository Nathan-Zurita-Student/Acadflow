<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\DeleteAccountRequest;
use App\Services\Auth\AccountDeletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public function __construct(private readonly AccountDeletionService $service) {}

    /** Exclui a conta (soft delete) após confirmar a senha. */
    public function destroy(DeleteAccountRequest $request): JsonResponse
    {
        $user = $request->user();

        $this->service->delete($user);

        // Encerra também a sessão atual do request.
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Conta excluída com sucesso.']);
    }
}
