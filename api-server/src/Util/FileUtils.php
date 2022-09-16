<?php

declare(strict_types=1);

namespace TryAgainLater\MediaConvertAppApi\Util;

use Psr\Http\Message\UploadedFileInterface;

class FileUtils
{
    public const UNITS = [
        'B',
        'Kb',
        'Mb',
        'Gb',
        'Tb',
        'Pb',
    ];

    public const MIME_TYPES_TO_EXTENSIONS = [
        'video/webm' => 'webm',
        'video/mp4' => 'mp4',
    ];

    public static function getMimeType(string $fileName): string | false
    {
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($fileInfo === false) {
            return false;
        }

        $mimeType = finfo_file($fileInfo, $fileName);
        finfo_close($fileInfo);

        return $mimeType;
    }

    /**
     * Returns an empty string in case it fails.
     */
    public static function getExtensionFromMimeType(string $mimeType): string
    {
        if (!isset(self::MIME_TYPES_TO_EXTENSIONS[strtolower($mimeType)])) {
            return '';
        }
        return '.' . self::MIME_TYPES_TO_EXTENSIONS[strtolower($mimeType)];
    }

    public static function bytesToHumanString(int $bytes, int $decimals = 2): string
    {
        $factor = intval(floor((strlen(strval($bytes)) - 1) / 3));
        if (!isset(self::UNITS[$factor])) {
            return strval($bytes);
        }
        if ($factor === 0) {
            $decimals = 0;
        }

        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . self::UNITS[$factor];
    }

    public static function moveUploadedFile(
        string $directory,
        UploadedFileInterface $uploadedFile,
    ): string {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $baseName = bin2hex(random_bytes(8));
        $fileName = sprintf('%s.%0.8s', $baseName, $extension);

        $uploadedFile->moveTo($directory . $fileName);
        return $fileName;
    }
}
