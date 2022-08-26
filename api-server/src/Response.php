<?php

declare(strict_types = 1);

namespace TryAgainLater\MediaConvertAppApi;

class Response
{
    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_OK = 200;
    public const HTTP_INTERNAL_SERVER_ERROR = 500;

    public static function json(int $httpCode, array $data): void
    {
        http_response_code($httpCode);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
