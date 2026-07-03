<?php

namespace App\Http\Controllers\Api\Auth;

use App\Events\Auth\UserRegistered;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

/**
 * Login com Google via sessão/cookie. Registrado no grupo `web` (sessão + CSRF
 * state), portanto usa o fluxo COM proteção de state (sem `stateless()`) e sem
 * trafegar token na URL — após o callback o usuário entra pela sessão.
 */
class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect('/login?error=google');
        }

        $user = User::where('google_id', $googleUser->getId())->first()
            ?? User::where('email', $googleUser->getEmail())->first();

        if (! $user) {
            $user = User::create([
                'name'              => $googleUser->getName() ?: ($googleUser->getNickname() ?: 'Usuário'),
                'email'             => $googleUser->getEmail(),
                'google_id'         => $googleUser->getId(),
                'avatar'            => $googleUser->getAvatar(),
                'password'          => null,
                'role'              => 'member',
                'email_verified_at' => now(), // Google já garante o e-mail
            ]);

            event(new UserRegistered($user));
        } else {
            // Vincula o Google a uma conta existente com o mesmo e-mail.
            $updates = [];
            if (! $user->google_id) {
                $updates['google_id'] = $googleUser->getId();
            }
            if (! $user->avatar && $googleUser->getAvatar()) {
                $updates['avatar'] = $googleUser->getAvatar();
            }
            if (! $user->hasVerifiedEmail()) {
                $updates['email_verified_at'] = now();
            }
            if ($updates) {
                $user->update($updates);
            }
        }

        Auth::guard('web')->login($user, remember: true);
        $request->session()->regenerate();
        $user->recordLogin($request->ip());

        return redirect('/');
    }
}
