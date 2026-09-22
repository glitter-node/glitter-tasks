<?php

namespace Modules\Glitter\Tasks\Services;

use App\Extension\HookManager;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Glitter\Tasks\Enums\TaskPriority;
use Modules\Glitter\Tasks\Enums\TaskStatus;
use Modules\Glitter\Tasks\Models\Task;
use Throwable;

/**
 * Task operation hook publisher.
 *
 * Hook payloads are immutable scalar snapshots. Publication is best-effort
 * and never changes the outcome of the Task operation that produced it.
 */
class TaskEventPublisher
{
    /**
     * @param  int|null  $actorId
     */
    public function afterCreate(Task $task, ?int $actorId): void
    {
        $this->publish(
            'glitter-tasks.task.after_create',
            (int) $task->getKey(),
            $actorId,
            [
                'task' => $this->taskSnapshot($task),
            ],
            static function (array $payload): void {
                HookManager::doAction('glitter-tasks.task.after_create', $payload);
            },
        );
    }

    /**
     * @param  list<string>  $changedFields
     */
    public function afterDetailsUpdated(Task $task, array $changedFields, ?int $actorId): void
    {
        $this->publish(
            'glitter-tasks.task.after_details_updated',
            (int) $task->getKey(),
            $actorId,
            [
                'changed_fields' => array_values(array_filter(
                    $changedFields,
                    static fn (mixed $field): bool => is_string($field)
                        && in_array($field, ['title', 'description', 'priority', 'due_at'], true),
                )),
            ],
            static function (array $payload): void {
                HookManager::doAction('glitter-tasks.task.after_details_updated', $payload);
            },
        );
    }

    public function afterAssigned(Task $task, ?int $fromAssigneeId, int $toAssigneeId, ?int $actorId): void
    {
        $this->publish(
            'glitter-tasks.task.after_assigned',
            (int) $task->getKey(),
            $actorId,
            [
                'from_assignee_id' => $fromAssigneeId,
                'to_assignee_id' => $toAssigneeId,
            ],
            static function (array $payload): void {
                HookManager::doAction('glitter-tasks.task.after_assigned', $payload);
            },
        );
    }

    public function afterUnassigned(Task $task, int $fromAssigneeId, ?int $actorId): void
    {
        $this->publish(
            'glitter-tasks.task.after_unassigned',
            (int) $task->getKey(),
            $actorId,
            [
                'from_assignee_id' => $fromAssigneeId,
                'to_assignee_id' => null,
            ],
            static function (array $payload): void {
                HookManager::doAction('glitter-tasks.task.after_unassigned', $payload);
            },
        );
    }

    public function afterStarted(Task $task, TaskStatus $fromStatus, ?int $actorId): void
    {
        $this->afterLifecycle(
            'glitter-tasks.task.after_started',
            $task,
            $fromStatus,
            static function (array $payload): void {
                HookManager::doAction('glitter-tasks.task.after_started', $payload);
            },
            $actorId,
        );
    }

    public function afterCompleted(Task $task, TaskStatus $fromStatus, ?int $actorId): void
    {
        $this->afterLifecycle(
            'glitter-tasks.task.after_completed',
            $task,
            $fromStatus,
            static function (array $payload): void {
                HookManager::doAction('glitter-tasks.task.after_completed', $payload);
            },
            $actorId,
        );
    }

    public function afterCancelled(Task $task, TaskStatus $fromStatus, ?int $actorId): void
    {
        $this->afterLifecycle(
            'glitter-tasks.task.after_cancelled',
            $task,
            $fromStatus,
            static function (array $payload): void {
                HookManager::doAction('glitter-tasks.task.after_cancelled', $payload);
            },
            $actorId,
        );
    }

    /**
     * @param  callable(array<string, mixed>): void  $dispatch
     */
    private function afterLifecycle(
        string $hookName,
        Task $task,
        TaskStatus $fromStatus,
        callable $dispatch,
        ?int $actorId,
    ): void {
        $this->publish(
            $hookName,
            (int) $task->getKey(),
            $actorId,
            [
                'from_status' => $fromStatus->value,
                'to_status' => $this->statusValue($task->status),
                'started_at' => $this->timestamp($task->started_at),
                'completed_at' => $this->timestamp($task->completed_at),
            ],
            $dispatch,
        );
    }

    /**
     * @param  array<string, mixed>  $operationPayload
     * @param  callable(array<string, mixed>): void  $dispatch
     */
    private function publish(
        string $hookName,
        int $taskId,
        ?int $actorId,
        array $operationPayload,
        callable $dispatch,
    ): void
    {
        $eventId = null;

        try {
            $eventId = (string) Str::uuid();
            $occurredAt = CarbonImmutable::now('UTC')->toIso8601String();
            $payload = array_merge([
                'event_id' => $eventId,
                'task_id' => $taskId,
                'actor_id' => $actorId,
                'occurred_at' => $occurredAt,
            ], $operationPayload);

            $connection = DB::connection();
            if ($connection->transactionLevel() > 0) {
                $connection->afterCommit(fn (): mixed => $this->dispatchSafely($hookName, $payload, $dispatch));

                return;
            }

            $this->dispatchSafely($hookName, $payload, $dispatch);
        } catch (Throwable $exception) {
            $this->logFailure($hookName, $eventId, $taskId, $exception);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  callable(array<string, mixed>): void  $dispatch
     */
    private function dispatchSafely(string $hookName, array $payload, callable $dispatch): void
    {
        try {
            $dispatch($payload);
        } catch (Throwable $exception) {
            $this->logFailure(
                $hookName,
                is_string($payload['event_id'] ?? null) ? $payload['event_id'] : null,
                isset($payload['task_id']) ? (int) $payload['task_id'] : null,
                $exception,
            );
        }
    }

    private function logFailure(string $hookName, ?string $eventId, ?int $taskId, Throwable $exception): void
    {
        try {
            Log::warning('Glitter Tasks hook publication failed.', [
                'hook' => $hookName,
                'event_id' => $eventId,
                'task_id' => $taskId,
                'exception' => $exception,
            ]);
        } catch (Throwable) {
            // Logging is best-effort and must not escape the containment path.
        }
    }

    /** @return array<string, scalar|null> */
    private function taskSnapshot(Task $task): array
    {
        return [
            'title' => (string) $task->title,
            'status' => $this->statusValue($task->status),
            'priority' => $this->priorityValue($task->priority),
            'created_by' => $task->created_by === null ? null : (int) $task->created_by,
            'assignee_id' => $task->assignee_id === null ? null : (int) $task->assignee_id,
            'due_at' => $this->timestamp($task->due_at),
        ];
    }

    private function statusValue(TaskStatus|string|null $status): ?string
    {
        return $status instanceof TaskStatus ? $status->value : ($status === null ? null : (string) $status);
    }

    private function priorityValue(TaskPriority|string|null $priority): ?string
    {
        return $priority instanceof TaskPriority ? $priority->value : ($priority === null ? null : (string) $priority);
    }

    private function timestamp(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $date = $value instanceof DateTimeInterface
            ? CarbonImmutable::instance($value)
            : CarbonImmutable::parse((string) $value);

        return $date->utc()->toIso8601String();
    }
}
