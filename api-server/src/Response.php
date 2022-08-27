<?php

declare(strict_types = 1);

namespace TryAgainLater\MediaConvertAppApi;

class Response
{
    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_UNAUTHORIZED = 401;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_OK = 200;
    public const HTTP_INTERNAL_SERVER_ERROR = 500;

    public static function json(int $httpCode, array $data = []): string
    {
        if (empty($data)) {
            if ($httpCode === self::HTTP_INTERNAL_SERVER_ERROR) {
                $data['error'] = 'Internal server error.';
            }
            if ($httpCode === self::HTTP_NOT_FOUND) {
                $data['error'] = 'Page not found.';
            }
        }

        http_response_code($httpCode);
        header('Content-Type: application/json');
        return json_encode($data);
    }

    public static function text(int $httpCode, string $data = ''): string
    {
        http_response_code($httpCode);
        header('Content-Type: text/plain');
        return $data;
    }
}
