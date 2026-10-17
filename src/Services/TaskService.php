<?php

namespace Modules\Glitter\Tasks\Services;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Enums\UserStatus;
use App\Support\Query\BoundedPage;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Modules\Glitter\Tasks\Enums\TaskPriority;
use Modules\Glitter\Tasks\Enums\TaskStatus;
use Modules\Glitter\Tasks\Exceptions\InvalidTaskAssignmentException;
use Modules\Glitter\Tasks\Exceptions\InvalidTaskTransitionException;
use Modules\Glitter\Tasks\Exceptions\InvalidTaskUpdateException;
use Modules\Glitter\Tasks\Models\Task;
use Modules\Glitter\Tasks\Repositories\TaskRepositoryInterface;

class TaskService
{
    private const DETAIL_FIELDS = ['title', 'description', 'priority', 'due_at'];

    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly TaskEventPublisher $taskEventPublisher,
    ) {}

    public function create(array $data): Task
    {
        $title = trim((string) ($data['title'] ?? ''));

        if ($title === '') {
            throw new InvalidArgumentException('Task title must not be empty.');
        }

        $creatorId = Auth::id();

        if ($creatorId === null) {
            throw new InvalidArgumentException('A creator identity is required to create a task.');
        }

        $task = $this->taskRepository->create([
            'title' => $title,
            'description' => $data['description'] ?? null,
            'due_at' => $data['due_at'] ?? null,
            'source_type' => $data['source_type'] ?? null,
            'source_id' => $data['source_id'] ?? null,
            'source_url' => $data['source_url'] ?? null,
            'metadata' => $data['metadata'] ?? null,
            'created_by' => $creatorId,
            'status' => TaskStatus::Open,
            'priority' => TaskPriority::Normal,
            'started_at' => null,
            'completed_at' => null,
        ]);

        $this->taskEventPublisher->afterCreate($task, $creatorId);

        return $task;
    }

    public function getTasks(
        ?TaskStatus $status = null,
        ?TaskPriority $priority = null,
        ?int $assigneeId = null,
        bool $unassigned = false,
        ?int $createdBy = null,
        int $page = 1,
        int $perPage = 20,
    ): BoundedPage {
        return $this->taskRepository->paginate(
            status: $status,
            priority: $priority,
            assigneeId: $assigneeId,
            unassigned: $unassigned,
            createdBy: $createdBy,
            page: $page,
            perPage: $perPage,
        );
    }

    public function getTaskOrFail(int $id): Task
    {
        return $this->taskRepository->findOrFail($id);
    }

    public function updateDetails(Task $task, array $validated): Task
    {
        if (array_diff(array_keys($validated), self::DETAIL_FIELDS) !== [] || $validated === []) {
            throw InvalidTaskUpdateException::invalidFields();
        }

        if (in_array($task->status, [TaskStatus::Completed, TaskStatus::Cancelled], true)) {
            throw InvalidTaskUpdateException::terminal($task->status);
        }

        $attributes = $validated;

        if (array_key_exists('title', $attributes)) {
            if (! is_string($attributes['title'])) {
                throw InvalidTaskUpdateException::invalidTitle();
            }

            $attributes['title'] = trim($attributes['title']);

            if ($attributes['title'] === '') {
                throw InvalidTaskUpdateException::invalidTitle();
            }
        }

        if (array_key_exists('description', $attributes) && $attributes['description'] === '') {
            $attributes['description'] = null;
        }

        if (array_key_exists('priority', $attributes)) {
            $priority = $attributes['priority'] instanceof TaskPriority
                ? $attributes['priority']
                : (is_string($attributes['priority']) ? TaskPriority::tryFrom($attributes['priority']) : null);

            if ($priority === null) {
                throw InvalidTaskUpdateException::invalidPriority();
            }

            $attributes['priority'] = $priority->value;
        }

        $updated = $this->taskRepository->updateDetails($task, $attributes);

        if ($updated === null) {
            throw InvalidTaskUpdateException::stale();
        }

        $changedFields = array_values(array_filter(
            array_keys($validated),
            static fn (mixed $field): bool => is_string($field) && in_array($field, self::DETAIL_FIELDS, true),
        ));

        if ($changedFields !== []) {
            $this->taskEventPublisher->afterDetailsUpdated($updated, $changedFields, Auth::id());
        }

        return $updated;
    }

    public function start(Task $task): Task
    {
        $expectedStatus = TaskStatus::Open;

        if ($task->status !== $expectedStatus) {
            throw $this->invalidTransition('start', $task, [$expectedStatus]);
        }

        $capturedAt = now();
        $transitioned = $this->taskRepository->transition($task, $expectedStatus, [
            'status' => TaskStatus::InProgress,
            'started_at' => $capturedAt,
            'completed_at' => null,
        ]);

        if ($transitioned === null) {
            throw $this->staleTransition('start', [$expectedStatus]);
        }

        $this->taskEventPublisher->afterStarted($transitioned, $expectedStatus, Auth::id());

        return $transitioned;
    }

    public function complete(Task $task): Task
    {
        $expectedStatus = TaskStatus::InProgress;

        if ($task->status !== $expectedStatus) {
            throw $this->invalidTransition('complete', $task, [$expectedStatus]);
        }

        $capturedAt = now();
        $transitioned = $this->taskRepository->transition($task, $expectedStatus, [
            'status' => TaskStatus::Completed,
            'started_at' => $task->started_at ?? $capturedAt,
            'completed_at' => $capturedAt,
        ]);

        if ($transitioned === null) {
            throw $this->staleTransition('complete', [$expectedStatus]);
        }

        $this->taskEventPublisher->afterCompleted($transitioned, $expectedStatus, Auth::id());

        return $transitioned;
    }

    public function cancel(Task $task): Task
    {
        $expectedStatus = $task->status;
        $allowedStatuses = [TaskStatus::Open, TaskStatus::InProgress];

        if (! in_array($expectedStatus, $allowedStatuses, true)) {
            throw $this->invalidTransition('cancel', $task, $allowedStatuses);
        }

        // 취소는 lifecycle 시각을 바꾸지 않지만 하나의 전이 작업 경계로 처리한다.
        $capturedAt = now();
        unset($capturedAt);

        $transitioned = $this->taskRepository->transition($task, $expectedStatus, [
            'status' => TaskStatus::Cancelled,
            'completed_at' => null,
        ]);

        if ($transitioned === null) {
            throw $this->staleTransition('cancel', $allowedStatuses);
        }

        $this->taskEventPublisher->afterCancelled($transitioned, $expectedStatus, Auth::id());

        return $transitioned;
    }

    public function assign(Task $task, int $userId): Task
    {
        $expectedStatus = $this->assertAssignmentStatus($task, 'assign');
        $user = $this->userRepository->findById($userId);

        if ($user === null || $user->status !== UserStatus::Active->value || $user->withdrawn_at !== null) {
            throw InvalidTaskAssignmentException::invalidTarget('assign');
        }

        $assigned = $this->taskRepository->updateAssignee(
            $task,
            $expectedStatus,
            $this->assigneeId($task),
            $userId,
        );

        if ($assigned === null) {
            throw InvalidTaskAssignmentException::stale('assign');
        }

        $fromAssigneeId = $this->assigneeId($task);
        $toAssigneeId = $this->assigneeId($assigned);

        if ($fromAssigneeId !== $toAssigneeId && $toAssigneeId !== null) {
            $this->taskEventPublisher->afterAssigned($assigned, $fromAssigneeId, $toAssigneeId, Auth::id());
        }

        return $assigned;
    }

    public function unassign(Task $task): Task
    {
        $expectedStatus = $this->assertAssignmentStatus($task, 'unassign');
        $unassigned = $this->taskRepository->updateAssignee(
            $task,
            $expectedStatus,
            $this->assigneeId($task),
            null,
        );

        if ($unassigned === null) {
            throw InvalidTaskAssignmentException::stale('unassign');
        }

        $fromAssigneeId = $this->assigneeId($task);

        if ($fromAssigneeId !== null && $this->assigneeId($unassigned) === null) {
            $this->taskEventPublisher->afterUnassigned($unassigned, $fromAssigneeId, Auth::id());
        }

        return $unassigned;
    }

    /**
     * @param  list<TaskStatus>  $expectedStatuses
     */
    private function invalidTransition(string $operation, Task $task, array $expectedStatuses): InvalidTaskTransitionException
    {
        return new InvalidTaskTransitionException($operation, $task->status, $expectedStatuses);
    }

    /**
     * @param  list<TaskStatus>  $expectedStatuses
     */
    private function staleTransition(string $operation, array $expectedStatuses): InvalidTaskTransitionException
    {
        return new InvalidTaskTransitionException($operation, null, $expectedStatuses, true);
    }

    private function assertAssignmentStatus(Task $task, string $operation): TaskStatus
    {
        $status = $task->status;

        if (! in_array($status, [TaskStatus::Open, TaskStatus::InProgress], true)) {
            throw InvalidTaskAssignmentException::statusNotAllowed($operation, $status);
        }

        return $status;
    }

    private function assigneeId(Task $task): ?int
    {
        return $task->assignee_id === null ? null : (int) $task->assignee_id;
    }
}
