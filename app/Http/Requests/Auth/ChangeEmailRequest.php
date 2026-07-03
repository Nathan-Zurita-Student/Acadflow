<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ChangeEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => Str::lower(trim((string) $this->input('email')))]);
        }
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password'],
            'email'            => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.current_password' => 'A senha atual está incorreta.',
            'email.unique'                      => 'Este e-mail já está em uso.',
        ];
    }
}
