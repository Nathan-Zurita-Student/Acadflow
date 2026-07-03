<?php

namespace App\Http\Requests\Auth;

use App\Support\PasswordPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class ResetPasswordRequest extends FormRequest
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
            'email'    => ['required', 'email', 'max:255'],
            'code'     => ['required', 'string', 'digits:6'],
            'password' => PasswordPolicy::rules(),
        ];
    }

    public function messages(): array
    {
        return [
            'code.digits' => 'O código deve ter 6 dígitos.',
        ];
    }
}
