<?php

declare(strict_types=1);

namespace TryAgainLater\MediaConvertAppApi\Application;

class Settings
{
    public function __construct(private array $settings = [])
    {
    }

    public function get(string $key = '', mixed $default = null)
    {
        if (empty($key)) {
            return $this->settings;
        }

        if (!isset($this->settings[$key])) {
            return $default;
        }

        return $this->settings[$key];
    }
}
