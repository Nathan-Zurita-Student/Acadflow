<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'        => ['sometimes', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            // Aceita qualquer coluna que exista no Kanban deste projeto.
            'status'       => ['nullable', Rule::exists('project_columns', 'key')
                                ->where('project_id', $this->route('project')->id)],
            'priority'     => ['nullable', 'in:low,medium,high,urgent'],
            'due_date'     => ['nullable', 'date'],
            'start_date'   => ['nullable', 'date'],
            'position'     => ['nullable', 'integer', 'min:0'],
            'assignee_ids' => ['nullable', 'array'],
            'assignee_ids.*' => ['integer', 'exists:users,id'],
            'tag_ids'      => ['nullable', 'array'],
            'tag_ids.*'    => ['integer', 'exists:tags,id'],
        ];
    }
}
