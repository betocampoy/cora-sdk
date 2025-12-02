<?php

namespace BetoCampoy\CoraSdk\Exception;

class ApiException extends CoraException
{
    public function __construct(
        string $message,
        private int $statusCode,
        private ?array $responseBody = null
    ) {
        parent::__construct($message, $statusCode);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getResponseBody(): ?array
    {
        return $this->responseBody;
    }
}
