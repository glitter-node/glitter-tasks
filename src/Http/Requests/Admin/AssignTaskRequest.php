<?php

namespace Modules\Glitter\Tasks\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AssignTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'assignee_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
