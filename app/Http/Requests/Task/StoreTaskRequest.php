<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'status'       => ['nullable', 'in:backlog,pending,in_progress,review,done'],
            'priority'     => ['nullable', 'in:low,medium,high,urgent'],
            'due_date'     => ['nullable', 'date'],
            'assignee_ids' => ['nullable', 'array'],
            'assignee_ids.*' => ['integer', 'exists:users,id'],
            'tag_ids'      => ['nullable', 'array'],
            'tag_ids.*'    => ['integer', 'exists:tags,id'],
        ];
    }
}
