<?php

declare(strict_types = 1);

namespace TryAgainLater\MediaConvertAppApi;

class Request
{
    public static function post(): bool
    {
        return strtoupper($_SERVER['REQUEST_METHOD']) === 'POST';
    }

    public static function get(): bool
    {
        return strtoupper($_SERVER['REQUEST_METHOD']) === 'GET';
    }

    public static function uriMatches(string $pattern): bool
    {
        $uri = explode('?', $_SERVER['REQUEST_URI'])[0];
        return preg_match("<^{$pattern}$>", $uri) === 1;
    }
}
