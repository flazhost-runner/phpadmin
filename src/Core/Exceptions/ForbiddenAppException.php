<?php

declare(strict_types=1);

namespace PHPAdmin\Core\Exceptions;

/**
 * 403 Forbidden exception.
 */
class ForbiddenAppException extends AppException
{
    public function __construct(string $message = 'Forbidden', ?\Throwable $previous = null)
    {
        parent::__construct($message, 403, $previous);
    }
}
