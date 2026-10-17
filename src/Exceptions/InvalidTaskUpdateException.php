<?php

namespace Modules\Glitter\Tasks\Exceptions;

use Modules\Glitter\Tasks\Enums\TaskStatus;
use RuntimeException;
use Throwable;

class InvalidTaskUpdateException extends RuntimeException
{
    private const REASON_INVALID_FIELDS = 'invalid_fields';

    private const REASON_INVALID_TITLE = 'invalid_title';

    private const REASON_INVALID_PRIORITY = 'invalid_priority';

    private const REASON_TERMINAL = 'terminal';

    private const REASON_STALE = 'stale';

    private function __construct(
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

    public static function invalidFields(): self
    {
        return new self(self::REASON_INVALID_FIELDS);
    }

    public static function invalidTitle(): self
    {
        return new self(self::REASON_INVALID_TITLE);
    }

    public static function invalidPriority(): self
    {
        return new self(self::REASON_INVALID_PRIORITY);
    }

    public static function terminal(TaskStatus $currentStatus): self
    {
        return new self(self::REASON_TERMINAL, $currentStatus);
    }

    public static function stale(): self
    {
        return new self(self::REASON_STALE);
    }

    public function getMessageKey(): string
    {
        return match ($this->reason) {
            self::REASON_INVALID_FIELDS => 'glitter-tasks::exceptions.invalid_update_fields',
            self::REASON_INVALID_TITLE => 'glitter-tasks::exceptions.invalid_update_title',
            self::REASON_INVALID_PRIORITY => 'glitter-tasks::exceptions.invalid_update_priority',
            self::REASON_TERMINAL => 'glitter-tasks::exceptions.update_terminal',
            self::REASON_STALE => 'glitter-tasks::exceptions.stale_update',
        };
    }

    /** @return array{current: string, expected: string} */
    public function getMessageParams(): array
    {
        return [
            'current' => $this->currentStatus?->value ?? 'unknown',
            'expected' => TaskStatus::Open->value . ', ' . TaskStatus::InProgress->value,
        ];
    }
}
