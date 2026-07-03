<?php

declare(strict_types=1);

namespace PHPAdmin\Core\Exceptions;

/**
 * 401 Unauthorized exception.
 */
class UnauthorizedAppException extends AppException
{
    public function __construct(string $message = 'Unauthorized', ?\Throwable $previous = null)
    {
        parent::__construct($message, 401, $previous);
    }
}
