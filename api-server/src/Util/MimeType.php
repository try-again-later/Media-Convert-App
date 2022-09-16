<?php

declare(strict_types=1);

namespace TryAgainLater\MediaConvertAppApi\Util;

enum MimeType: string
{
    case VIDEO_MP4 = 'video/mp4';
    case VIDEO_WEBM = 'video/webm';

    public function getExtension(): string
    {
        return match ($this) {
            self::VIDEO_MP4 => 'mp4',
            self::VIDEO_WEBM => 'webm',
        };
    }
}
