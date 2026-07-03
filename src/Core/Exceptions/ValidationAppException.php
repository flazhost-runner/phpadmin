<?php

declare(strict_types=1);

namespace PHPAdmin\Core\Exceptions;

/**
 * 422 Unprocessable Entity (validation) exception.
 */
class ValidationAppException extends AppException
{
    /**
     * @param array<string, string> $errors Field-level validation errors.
     */
    public function __construct(
        string $message = 'Validation failed',
        private readonly array $errors = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 422, $previous);
    }

    /**
     * @return array<string, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
