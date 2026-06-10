<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $avatarUrl = null;
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $avatarUrl = Storage::url($path);
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => $request->password,
            'role'     => 'member',
            'avatar'   => $avatarUrl,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $this->userResource($user),
            'token' => $token,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas.'],
            ]);
        }

        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $this->userResource($user),
            'token' => $token,
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'name'         => ['sometimes', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'email'        => ['sometimes', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password'     => ['nullable', 'string', 'min:8', 'confirmed'],
            'avatar'       => ['nullable', 'image', 'max:2048'],
        ]);

        $data = $request->only(['name', 'display_name', 'email']);

        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                $oldPath = str_replace('/storage/', '', parse_url($user->avatar, PHP_URL_PATH));
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = Storage::url($path);
        }

        $user->update($data);

        return response()->json($this->userResource($user->fresh()));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout realizado com sucesso.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($this->userResource($request->user()));
    }

    private function userResource(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'display_name' => $user->display_name,
            'email' => $user->email,
            'role' => $user->role,
            'avatar' => $user->avatar,
            'created_at' => $user->created_at,
        ];
    }
}
