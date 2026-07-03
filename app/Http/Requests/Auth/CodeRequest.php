<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/** Request genérico para endpoints que recebem apenas um código OTP de 6 dígitos. */
class CodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.digits'   => 'O código deve ter 6 dígitos.',
            'code.required' => 'Informe o código.',
        ];
    }
}
