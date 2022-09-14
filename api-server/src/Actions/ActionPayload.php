<?php

declare(strict_types=1);

namespace TryAgainLater\MediaConvertAppApi\Actions;

use JsonSerializable;

class ActionPayload implements JsonSerializable
{
    public function __construct(
        private int $statusCode = 200,
        private array | object | null $data = null,
        private ?ActionError $error = null,
    ) {
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getData(): array | object | null
    {
        return $this->data;
    }

    public function getError(): ?ActionError
    {
        return $this->error;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        $payload = [
            'statusCode' => $this->statusCode,
        ];

        if ($this->data !== null) {
            $payload['data'] = $this->data;
        } else if ($this->error !== null) {
            $payload['error'] = $this->error;
        }

        return $payload;
    }
}
