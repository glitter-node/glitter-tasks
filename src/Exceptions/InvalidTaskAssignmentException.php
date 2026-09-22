<?php

namespace Modules\Glitter\Tasks\Exceptions;

use Modules\Glitter\Tasks\Enums\TaskStatus;
use RuntimeException;
use Throwable;

class InvalidTaskAssignmentException extends RuntimeException
{
    private const REASON_INVALID_TARGET = 'invalid_target';

    private const REASON_STATUS_NOT_ALLOWED = 'status_not_allowed';

    private const REASON_STALE = 'stale';

    private function __construct(
        public readonly string $operation,
        public readonly string $reason,
        public readonly ?TaskStatus $currentStatus = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            __($this->getMessageKey(), $this->getMessageParams()),
            0,
            $previous,
        );
    }

    public static function invalidTarget(string $operation): self
    {
        return new self($operation, self::REASON_INVALID_TARGET);
    }

    public static function statusNotAllowed(string $operation, TaskStatus $currentStatus): self
    {
        return new self($operation, self::REASON_STATUS_NOT_ALLOWED, $currentStatus);
    }

    public static function stale(string $operation): self
    {
        return new self($operation, self::REASON_STALE);
    }

    public function getMessageKey(): string
    {
        return match ($this->reason) {
            self::REASON_INVALID_TARGET => 'glitter-tasks::exceptions.invalid_assignment_target',
            self::REASON_STATUS_NOT_ALLOWED => 'glitter-tasks::exceptions.assignment_status_not_allowed',
            self::REASON_STALE => 'glitter-tasks::exceptions.stale_assignment',
        };
    }

    /** @return array{operation: string, current: string, expected: string} */
    public function getMessageParams(): array
    {
        return [
            'operation' => $this->operation,
            'current' => $this->currentStatus?->value ?? 'unknown',
            'expected' => TaskStatus::Open->value . ', ' . TaskStatus::InProgress->value,
        ];
    }
}
