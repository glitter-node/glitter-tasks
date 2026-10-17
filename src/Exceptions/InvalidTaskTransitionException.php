<?php

namespace Modules\Glitter\Tasks\Exceptions;

use Modules\Glitter\Tasks\Enums\TaskStatus;
use RuntimeException;
use Throwable;

class InvalidTaskTransitionException extends RuntimeException
{
    /**
     * @param  list<TaskStatus>  $expectedStatuses
     */
    public function __construct(
        public readonly string $operation,
        public readonly ?TaskStatus $currentStatus,
        public readonly array $expectedStatuses,
        public readonly bool $stale = false,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            __($this->getMessageKey(), $this->getMessageParams()),
            0,
            $previous,
        );
    }

    public function getMessageKey(): string
    {
        return $this->stale
            ? 'glitter-tasks::exceptions.stale_transition'
            : 'glitter-tasks::exceptions.invalid_transition';
    }

    /**
     * @return array{operation: string, current: string, expected: string}
     */
    public function getMessageParams(): array
    {
        return [
            'operation' => $this->operation,
            'current' => $this->currentStatus?->value ?? 'unknown',
            'expected' => implode(', ', array_map(
                static fn (TaskStatus $status): string => $status->value,
                $this->expectedStatuses,
            )),
        ];
    }
}
