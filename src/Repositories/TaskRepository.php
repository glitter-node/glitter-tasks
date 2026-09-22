<?php

namespace Modules\Glitter\Tasks\Repositories;

use App\Support\Query\BoundedPage;
use App\Support\Query\BoundedPaginator;
use App\Support\Query\PaginationLimits;
use Illuminate\Support\Facades\DB;
use Modules\Glitter\Tasks\Enums\TaskPriority;
use Modules\Glitter\Tasks\Enums\TaskStatus;
use Modules\Glitter\Tasks\Models\Task;

class TaskRepository implements TaskRepositoryInterface
{
    public function findById(int $id): ?Task
    {
        return Task::query()->find($id);
    }

    public function findOrFail(int $id): Task
    {
        return Task::query()->findOrFail($id);
    }

    public function create(array $data): Task
    {
        return Task::query()->create($data);
    }

    public function update(Task $task, array $data): Task
    {
        $task->fill($data);
        $task->save();

        return $task->refresh();
    }

    public function updateDetails(Task $task, array $attributes): ?Task
    {
        return DB::transaction(function () use ($task, $attributes): ?Task {
            $current = Task::query()
                ->whereKey($task->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($current->status, [TaskStatus::Completed, TaskStatus::Cancelled], true)) {
                return null;
            }

            $editable = array_intersect_key($attributes, array_flip([
                'title',
                'description',
                'priority',
                'due_at',
            ]));

            $current->newQuery()->whereKey($current->getKey())->update($editable);

            return $current->refresh();
        });
    }

    public function transition(Task $task, TaskStatus $expectedStatus, array $attributes): ?Task
    {
        $updated = $task->newQuery()
            ->whereKey($task->getKey())
            ->where('status', $expectedStatus->value)
            ->update($attributes);

        if ($updated !== 1) {
            return null;
        }

        return $task->refresh();
    }

    public function updateAssignee(
        Task $task,
        TaskStatus $expectedStatus,
        ?int $expectedAssigneeId,
        ?int $requestedAssigneeId,
    ): ?Task {
        return DB::transaction(function () use (
            $task,
            $expectedStatus,
            $expectedAssigneeId,
            $requestedAssigneeId,
        ): ?Task {
            $current = Task::query()
                ->whereKey($task->getKey())
                ->lockForUpdate()
                ->first();

            if ($current === null) {
                return null;
            }

            $currentAssigneeId = $current->assignee_id === null
                ? null
                : (int) $current->assignee_id;

            if ($current->status !== $expectedStatus || $currentAssigneeId !== $expectedAssigneeId) {
                return null;
            }

            if ($currentAssigneeId === $requestedAssigneeId) {
                return $current;
            }

            $current->newQuery()->whereKey($current->getKey())->update([
                'assignee_id' => $requestedAssigneeId,
            ]);

            return $current->refresh();
        });
    }

    public function paginate(
        ?TaskStatus $status = null,
        ?TaskPriority $priority = null,
        ?int $assigneeId = null,
        bool $unassigned = false,
        ?int $createdBy = null,
        int $page = 1,
        int $perPage = 20,
    ): BoundedPage {
        $query = Task::query()
            ->when(
                $status !== null,
                fn ($query) => $query->where('status', $status->value),
            )
            ->when(
                $priority !== null,
                fn ($query) => $query->where('priority', $priority->value),
            )
            ->when(
                $assigneeId !== null,
                fn ($query) => $query->where('assignee_id', $assigneeId),
            )
            ->when(
                $unassigned,
                fn ($query) => $query->whereNull('assignee_id'),
            )
            ->when(
                $createdBy !== null,
                fn ($query) => $query->where('created_by', $createdBy),
            );

        return BoundedPaginator::paginate(
            query: $query->orderByDesc('created_at')->orderByDesc('id'),
            perPage: $perPage,
            page: $page,
            resultCap: PaginationLimits::resultCap('admin.tasks'),
            columns: [
                'id',
                'title',
                'description',
                'status',
                'priority',
                'created_by',
                'assignee_id',
                'due_at',
                'started_at',
                'completed_at',
                'created_at',
                'updated_at',
            ],
        );
    }
}
