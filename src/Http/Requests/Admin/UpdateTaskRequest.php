<?php

namespace Modules\Glitter\Tasks\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rule;
use Modules\Glitter\Tasks\Enums\TaskPriority;

class UpdateTaskRequest extends FormRequest
{
    private const EDITABLE_FIELDS = ['title', 'description', 'priority', 'due_at'];

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'priority' => ['sometimes', 'string', Rule::in(array_column(TaskPriority::cases(), 'value'))],
            'due_at' => ['sometimes', 'nullable', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (array_intersect(array_keys($validator->getData()), self::EDITABLE_FIELDS) === []) {
                $validator->errors()->add(
                    'task',
                    __('glitter-tasks::messages.task.editable_field_required'),
                );
            }
        });
    }
}
