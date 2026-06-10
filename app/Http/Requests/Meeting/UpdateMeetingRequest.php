<?php

namespace App\Http\Requests\Meeting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMeetingRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'        => ['sometimes', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'scheduled_at' => ['sometimes', 'date'],
            'location'     => ['nullable', 'string', 'max:255'],
            'notes'        => ['nullable', 'string'],
        ];
    }
}
