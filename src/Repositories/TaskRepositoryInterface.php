<?php

namespace Modules\Glitter\Tasks\Repositories;

use App\Support\Query\BoundedPage;
use Modules\Glitter\Tasks\Enums\TaskPriority;
use Modules\Glitter\Tasks\Enums\TaskStatus;
use Modules\Glitter\Tasks\Models\Task;

interface TaskRepositoryInterface
{
    public function findById(int $id): ?Task;

    public function findOrFail(int $id): Task;

    public function create(array $data): Task;

    public function update(Task $task, array $data): Task;

    public function updateDetails(Task $task, array $attributes): ?Task;

    public function transition(Task $task, TaskStatus $expectedStatus, array $attributes): ?Task;

    public function updateAssignee(
        Task $task,
        TaskStatus $expectedStatus,
        ?int $expectedAssigneeId,
        ?int $requestedAssigneeId,
    ): ?Task;

    public function paginate(
        ?TaskStatus $status = null,
        ?TaskPriority $priority = null,
        ?int $assigneeId = null,
        bool $unassigned = false,
        ?int $createdBy = null,
        int $page = 1,
        int $perPage = 20,
    ): BoundedPage;
}
