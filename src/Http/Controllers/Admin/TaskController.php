<?php

namespace Modules\Glitter\Tasks\Http\Controllers\Admin;

use App\Http\Controllers\Api\Base\AdminBaseController;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Modules\Glitter\Tasks\Enums\TaskPriority;
use Modules\Glitter\Tasks\Enums\TaskStatus;
use Modules\Glitter\Tasks\Exceptions\InvalidTaskAssignmentException;
use Modules\Glitter\Tasks\Exceptions\InvalidTaskTransitionException;
use Modules\Glitter\Tasks\Exceptions\InvalidTaskUpdateException;
use Modules\Glitter\Tasks\Http\Requests\Admin\AssignTaskRequest;
use Modules\Glitter\Tasks\Http\Requests\Admin\StoreTaskRequest;
use Modules\Glitter\Tasks\Http\Requests\Admin\TaskIndexRequest;
use Modules\Glitter\Tasks\Http\Requests\Admin\UpdateTaskRequest;
use Modules\Glitter\Tasks\Http\Resources\TaskCollection;
use Modules\Glitter\Tasks\Http\Resources\TaskResource;
use Modules\Glitter\Tasks\Models\Task;
use Modules\Glitter\Tasks\Services\TaskService;

class TaskController extends AdminBaseController
{
    public function __construct(private readonly TaskService $taskService)
    {
        parent::__construct();
    }

    public function index(TaskIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $status = isset($validated['status']) && $validated['status'] !== null
            ? TaskStatus::from($validated['status'])
            : null;
        $priority = isset($validated['priority']) && $validated['priority'] !== null
            ? TaskPriority::from($validated['priority'])
            : null;
        $tasks = $this->taskService->getTasks(
            status: $status,
            priority: $priority,
            assigneeId: isset($validated['assignee_id']) && $validated['assignee_id'] !== null
                ? (int) $validated['assignee_id']
                : null,
            unassigned: filter_var($validated['unassigned'] ?? false, FILTER_VALIDATE_BOOLEAN),
            createdBy: isset($validated['created_by']) && $validated['created_by'] !== null
                ? (int) $validated['created_by']
                : null,
            page: $validated['page'] ?? 1,
            perPage: $validated['per_page'] ?? 20,
        );

        return $this->successWithResource(
            'glitter-tasks::messages.task.fetch_success',
            new TaskCollection($tasks),
        );
    }

    public function show(int $task): JsonResponse
    {
        try {
            $taskModel = $this->taskService->getTaskOrFail($task);
        } catch (ModelNotFoundException) {
            return $this->notFound('glitter-tasks::messages.task.not_found');
        }

        return $this->successWithResource(
            'glitter-tasks::messages.task.fetch_success',
            new TaskResource($taskModel),
        );
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = $this->taskService->create($request->validated());

        return $this->successWithResource(
            'glitter-tasks::messages.task.create_success',
            new TaskResource($task),
            201,
        );
    }

    public function update(UpdateTaskRequest $request, int $task): JsonResponse
    {
        $taskModel = $this->taskOrNotFound($task);

        if ($taskModel instanceof JsonResponse) {
            return $taskModel;
        }

        try {
            $taskModel = $this->taskService->updateDetails($taskModel, $request->validated());
        } catch (ModelNotFoundException) {
            return $this->notFound('glitter-tasks::messages.task.not_found');
        } catch (InvalidTaskUpdateException $exception) {
            return $this->updateError($exception);
        }

        return $this->successWithResource(
            'glitter-tasks::messages.task.update_success',
            new TaskResource($taskModel),
        );
    }

    public function assign(AssignTaskRequest $request, int $task): JsonResponse
    {
        $taskModel = $this->taskOrNotFound($task);

        if ($taskModel instanceof JsonResponse) {
            return $taskModel;
        }

        try {
            $taskModel = $this->taskService->assign(
                $taskModel,
                (int) $request->validated()['assignee_id'],
            );
        } catch (InvalidTaskAssignmentException $exception) {
            return $this->assignmentError($exception);
        }

        return $this->successWithResource(
            'glitter-tasks::messages.task.assign_success',
            new TaskResource($taskModel),
        );
    }

    public function unassign(int $task): JsonResponse
    {
        $taskModel = $this->taskOrNotFound($task);

        if ($taskModel instanceof JsonResponse) {
            return $taskModel;
        }

        try {
            $taskModel = $this->taskService->unassign($taskModel);
        } catch (InvalidTaskAssignmentException $exception) {
            return $this->assignmentError($exception);
        }

        return $this->successWithResource(
            'glitter-tasks::messages.task.unassign_success',
            new TaskResource($taskModel),
        );
    }

    public function start(int $task): JsonResponse
    {
        return $this->transition($task, 'start', 'start_success');
    }

    public function complete(int $task): JsonResponse
    {
        return $this->transition($task, 'complete', 'complete_success');
    }

    public function cancel(int $task): JsonResponse
    {
        return $this->transition($task, 'cancel', 'cancel_success');
    }

    private function transition(int $task, string $operation, string $successMessage): JsonResponse
    {
        $taskModel = $this->taskOrNotFound($task);

        if ($taskModel instanceof JsonResponse) {
            return $taskModel;
        }

        try {
            $taskModel = match ($operation) {
                'start' => $this->taskService->start($taskModel),
                'complete' => $this->taskService->complete($taskModel),
                'cancel' => $this->taskService->cancel($taskModel),
            };
        } catch (InvalidTaskTransitionException $exception) {
            return $this->transitionError($exception);
        }

        return $this->successWithResource(
            'glitter-tasks::messages.task.'.$successMessage,
            new TaskResource($taskModel),
        );
    }

    private function taskOrNotFound(int $id): Task|JsonResponse
    {
        try {
            return $this->taskService->getTaskOrFail($id);
        } catch (ModelNotFoundException) {
            return $this->notFound('glitter-tasks::messages.task.not_found');
        }
    }

    private function assignmentError(InvalidTaskAssignmentException $exception): JsonResponse
    {
        $status = $exception->reason === 'stale' ? 409 : 422;
        $code = $exception->reason === 'stale'
            ? 'stale_task_assignment'
            : 'invalid_task_assignment';

        return $this->error(
            $exception->getMessageKey(),
            $status,
            ['code' => $code],
            $exception->getMessageParams(),
        );
    }

    private function transitionError(InvalidTaskTransitionException $exception): JsonResponse
    {
        return $this->error(
            $exception->getMessageKey(),
            $exception->stale ? 409 : 422,
            ['code' => $exception->stale ? 'stale_task_transition' : 'invalid_task_transition'],
            $exception->getMessageParams(),
        );
    }

    private function updateError(InvalidTaskUpdateException $exception): JsonResponse
    {
        return $this->error(
            $exception->getMessageKey(),
            $exception->reason === 'stale' ? 409 : 422,
            ['code' => $exception->reason === 'stale' ? 'stale_task_update' : 'invalid_task_update'],
            $exception->getMessageParams(),
        );
    }
}
