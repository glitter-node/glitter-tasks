<?php

namespace Modules\Glitter\Tasks\Http\Requests\Admin;

use App\Support\Query\PaginationLimits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Glitter\Tasks\Enums\TaskStatus;
use Modules\Glitter\Tasks\Enums\TaskPriority;

class TaskIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (['status', 'priority', 'assignee_id', 'unassigned', 'created_by'] as $field) {
            if (! $this->has($field)) {
                continue;
            }

            $value = $this->input($field);

            if ($value === '') {
                $normalized[$field] = null;
            } elseif ($field === 'unassigned' && ($value === 'true' || $value === 'false')) {
                $normalized[$field] = $value === 'true';
            }
        }

        if ($normalized !== []) {
            $this->merge($normalized);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', Rule::in(array_column(TaskStatus::cases(), 'value'))],
            'priority' => ['nullable', 'string', Rule::in(array_column(TaskPriority::cases(), 'value'))],
            'assignee_id' => ['nullable', 'integer', 'min:1'],
            'unassigned' => ['nullable', 'boolean'],
            'created_by' => ['nullable', 'integer', 'min:1'],
            'page' => array_filter([
                'nullable',
                'integer',
                'min:1',
                ($maxPage = PaginationLimits::maxPage('admin.tasks')) !== null ? 'max:'.$maxPage : null,
            ]),
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $data = $validator->getData();
            $unassigned = $data['unassigned'] ?? null;
            $isUnassigned = $unassigned === true || $unassigned === 1 || $unassigned === '1';

            if ($isUnassigned && array_key_exists('assignee_id', $data) && $data['assignee_id'] !== null) {
                $validator->errors()->add(
                    'unassigned',
                    __('glitter-tasks::messages.task.unassigned_assignee_conflict'),
                );
            }
        });
    }
}
