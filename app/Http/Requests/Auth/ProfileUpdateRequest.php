<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Atualização de dados básicos do perfil. E-mail e senha NÃO são alterados
 * aqui — possuem fluxos próprios e seguros (com senha atual e/ou código).
 */
class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'         => ['sometimes', 'required', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'avatar'       => ['nullable', 'image', 'max:20480'],
        ];
    }
}
