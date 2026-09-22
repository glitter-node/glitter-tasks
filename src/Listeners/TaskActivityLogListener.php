<?php

namespace Modules\Glitter\Tasks\Listeners;

use App\Contracts\Extension\HookListenerInterface;
use App\Enums\ActivityLogType;
use Illuminate\Support\Facades\Log;
use Modules\Glitter\Tasks\Models\Task;

/**
 * Records the Task operation hook snapshots in G7's global Activity Log.
 *
 * The listener deliberately uses only the scalar hook payload. It never
 * reloads a Task or User and does not mutate the Task domain.
 */
class TaskActivityLogListener implements HookListenerInterface
{
    /**
     * @return array<string, array{method: string, priority: int, type: 'action'}>
     */
    public static function getSubscribedHooks(): array
    {
        return [
            'glitter-tasks.task.after_create' => ['method' => 'handleAfterCreate', 'priority' => 20],
            'glitter-tasks.task.after_details_updated' => ['method' => 'handleAfterDetailsUpdated', 'priority' => 20],
            'glitter-tasks.task.after_assigned' => ['method' => 'handleAfterAssigned', 'priority' => 20],
            'glitter-tasks.task.after_unassigned' => ['method' => 'handleAfterUnassigned', 'priority' => 20],
            'glitter-tasks.task.after_started' => ['method' => 'handleAfterStarted', 'priority' => 20],
            'glitter-tasks.task.after_completed' => ['method' => 'handleAfterCompleted', 'priority' => 20],
            'glitter-tasks.task.after_cancelled' => ['method' => 'handleAfterCancelled', 'priority' => 20],
        ];
    }

    public function handle(...$args): void
    {
        // Each subscribed hook is mapped to its operation-specific handler.
    }

    /** @param array<string, mixed> $payload */
    public function handleAfterCreate(array $payload): void
    {
        $this->record('task.created', 'task_created', $payload, []);
    }

    /** @param array<string, mixed> $payload */
    public function handleAfterDetailsUpdated(array $payload): void
    {
        $changedFields = array_values(array_filter(
            $payload['changed_fields'] ?? [],
            static fn (mixed $field): bool => is_string($field),
        ));

        $this->record(
            'task.details_updated',
            'task_details_updated',
            $payload,
            ['changed_fields' => $changedFields],
            ['changed_fields' => implode(', ', $changedFields)],
        );
    }

    /** @param array<string, mixed> $payload */
    public function handleAfterAssigned(array $payload): void
    {
        $properties = [
            'from_assignee_id' => $this->nullableInt($payload['from_assignee_id'] ?? null),
            'to_assignee_id' => $this->nullableInt($payload['to_assignee_id'] ?? null),
        ];

        $this->record(
            'task.assigned',
            'task_assigned',
            $payload,
            $properties,
            $properties,
        );
    }

    /** @param array<string, mixed> $payload */
    public function handleAfterUnassigned(array $payload): void
    {
        $properties = [
            'from_assignee_id' => $this->nullableInt($payload['from_assignee_id'] ?? null),
            'to_assignee_id' => null,
        ];

        $this->record(
            'task.unassigned',
            'task_unassigned',
            $payload,
            $properties,
            $properties,
        );
    }

    /** @param array<string, mixed> $payload */
    public function handleAfterStarted(array $payload): void
    {
        $this->recordLifecycle('task.started', 'task_started', $payload);
    }

    /** @param array<string, mixed> $payload */
    public function handleAfterCompleted(array $payload): void
    {
        $this->recordLifecycle('task.completed', 'task_completed', $payload);
    }

    /** @param array<string, mixed> $payload */
    public function handleAfterCancelled(array $payload): void
    {
        $this->recordLifecycle('task.cancelled', 'task_cancelled', $payload);
    }

    /** @param array<string, mixed> $payload */
    private function recordLifecycle(string $action, string $description, array $payload): void
    {
        $properties = [
            'from_status' => $this->nullableString($payload['from_status'] ?? null),
            'to_status' => $this->nullableString($payload['to_status'] ?? null),
        ];

        $this->record($action, $description, $payload, $properties, $properties);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, scalar|null|array>  $operationProperties
     * @param  array<string, scalar|null|array>  $descriptionParams
     */
    private function record(
        string $action,
        string $description,
        array $payload,
        array $operationProperties,
        array $descriptionParams = [],
    ): void {
        $taskId = $this->nullableInt($payload['task_id'] ?? null);
        if ($taskId === null) {
            return;
        }

        $eventId = is_string($payload['event_id'] ?? null) ? $payload['event_id'] : null;
        $occurredAt = is_string($payload['occurred_at'] ?? null) ? $payload['occurred_at'] : null;
        $actorId = $this->nullableInt($payload['actor_id'] ?? null);

        $properties = [
            'event_id' => $eventId,
            'occurred_at' => $occurredAt,
            ...$operationProperties,
        ];

        $write = function () use ($action, $taskId, $actorId, $description, $descriptionParams, $properties): void {
            Log::channel('activity')->info($action, [
                'loggable_type' => Task::class,
                'loggable_id' => $taskId,
                'user_id' => $actorId,
                'log_type' => $actorId === null ? ActivityLogType::System : ActivityLogType::Admin,
                'description_key' => "glitter-tasks::activity_log.description.{$description}",
                'description_params' => [
                    'task_id' => $taskId,
                    ...$descriptionParams,
                ],
                'properties' => $properties,
            ]);
        };

        $write();
    }

    private function nullableInt(mixed $value): ?int
    {
        return is_int($value) || (is_string($value) && ctype_digit($value))
            ? (int) $value
            : null;
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }
}
