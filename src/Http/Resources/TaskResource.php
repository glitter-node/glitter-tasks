<?php

namespace Modules\Glitter\Tasks\Http\Resources;

use App\Http\Resources\BaseApiResource;
use Illuminate\Http\Request;

class TaskResource extends BaseApiResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status?->value,
            'priority' => $this->priority?->value,
            'created_by' => $this->created_by,
            'assignee_id' => $this->assignee_id,
            'due_at' => $this->due_at
                ? $this->formatDateTimeStringForUser($this->due_at)
                : null,
            'started_at' => $this->started_at
                ? $this->formatDateTimeStringForUser($this->started_at)
                : null,
            'completed_at' => $this->completed_at
                ? $this->formatDateTimeStringForUser($this->completed_at)
                : null,
            ...$this->formatTimestamps(),
        ];
    }
}
