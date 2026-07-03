<?php

declare(strict_types=1);

namespace PHPAdmin\Core\Exceptions;

/**
 * 404 Not Found exception.
 */
class NotFoundAppException extends AppException
{
    public function __construct(string $message = 'Not Found', ?\Throwable $previous = null)
    {
        parent::__construct($message, 404, $previous);
    }
}
