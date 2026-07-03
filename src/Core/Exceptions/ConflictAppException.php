<?php

declare(strict_types=1);

namespace PHPAdmin\Core\Exceptions;

/**
 * 409 Conflict exception.
 */
class ConflictAppException extends AppException
{
    public function __construct(string $message = 'Conflict', ?\Throwable $previous = null)
    {
        parent::__construct($message, 409, $previous);
    }
}
