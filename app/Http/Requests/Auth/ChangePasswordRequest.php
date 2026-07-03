<?php

namespace App\Http\Requests\Auth;

use App\Support\PasswordPolicy;
use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // valida contra a senha atual do usuário autenticado
            'current_password' => ['required', 'string', 'current_password'],
            'password'         => [...PasswordPolicy::rules(), 'different:current_password'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.current_password' => 'A senha atual está incorreta.',
            'password.different'                => 'A nova senha deve ser diferente da atual.',
        ];
    }
}
