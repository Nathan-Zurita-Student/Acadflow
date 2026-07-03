<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\SessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionManagementController extends Controller
{
    public function __construct(private readonly SessionService $sessions) {}

    /** Lista as sessões ativas do usuário (navegador/SO/IP/datas). */
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->sessions->forUser($request->user(), $request->session()->getId()),
        ]);
    }

    /** Encerra uma sessão específica do próprio usuário. */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->sessions->destroy($request->user(), $id);

        return response()->json(['message' => 'Sessão encerrada.']);
    }

    /** Encerra todas as sessões, exceto a atual. */
    public function destroyOthers(Request $request): JsonResponse
    {
        $this->sessions->destroyOthers($request->user(), $request->session()->getId());

        return response()->json(['message' => 'As demais sessões foram encerradas.']);
    }
}
