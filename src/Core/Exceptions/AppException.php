<?php

declare(strict_types=1);

namespace PHPAdmin\Core\Exceptions;

/**
 * Base application exception.
 */
class AppException extends \RuntimeException
{
    public function __construct(string $message = '', int $code = 400, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
