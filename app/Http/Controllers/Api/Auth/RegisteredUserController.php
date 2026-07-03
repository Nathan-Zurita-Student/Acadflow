<?php

namespace App\Http\Controllers\Api\Auth;

use App\Events\Auth\UserRegistered;
use App\Http\Controllers\Api\Auth\Concerns\RespondsWithUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\Auth\EmailVerificationService;
use App\Services\AvatarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class RegisteredUserController extends Controller
{
    use RespondsWithUser;

    public function __construct(
        private readonly EmailVerificationService $verification,
        private readonly AvatarService $avatars,
    ) {}

    public function store(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name'     => $request->string('name'),
            'email'    => $request->string('email'),
            'password' => $request->string('password'), // cast 'hashed' aplica o hash
            'role'     => 'member',
            'avatar'   => $request->hasFile('avatar')
                ? $this->avatars->store($request->file('avatar'))
                : null,
        ]);

        event(new UserRegistered($user));

        // Inicia a sessão já autenticada, mas o acesso ao app fica bloqueado
        // pelo middleware 'verified' até a confirmação do e-mail por código.
        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $user->recordLogin($request->ip());

        $this->verification->send($user);

        return $this->userResponse($user, 201);
    }
}
