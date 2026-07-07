<?php

namespace App\Http\Requests\Auth;

use App\Support\PasswordPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Normaliza o e-mail (trim + lowercase) e o nome (trim) antes de validar. */
    protected function prepareForValidation(): void
    {
        $this->merge(array_filter([
            'email' => $this->has('email') ? Str::lower(trim((string) $this->input('email'))) : null,
            'name'  => $this->has('name') ? trim((string) $this->input('name')) : null,
        ], fn ($v) => $v !== null));
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            // unique ignora soft-deleted implicitamente? Não — o e-mail é
            // liberado (anonimizado) no soft delete, então o unique padrão basta.
            'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => PasswordPolicy::rules(),
            'avatar'   => ['nullable', 'image', 'max:20480'],
            // LGPD: aceite explícito dos Termos de Uso e da Política de Privacidade.
            'terms'    => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'terms.accepted' => 'Você precisa aceitar os Termos de Uso e a Política de Privacidade.',
        ];
    }
}
