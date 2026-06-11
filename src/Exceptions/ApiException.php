<?php

namespace Clickem\UrlShortener\Exceptions;

class ApiException extends ClickemException
{
    public function __construct(
        string $message,
        private readonly int $statusCode,
        private readonly array $errors = [],
    ) {
        parent::__construct($message, $statusCode);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
