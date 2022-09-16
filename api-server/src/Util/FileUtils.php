<?php

declare(strict_types=1);

namespace TryAgainLater\MediaConvertAppApi\Util;

use Psr\Http\Message\UploadedFileInterface;

class FileUtils
{
    public const SIZE_UNITS = [
        'B',
        'Kb',
        'Mb',
        'Gb',
        'Tb',
        'Pb',
    ];

    public static function getMimeType(string $fileName): MimeType | bool
    {
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($fileInfo === false) {
            return false;
        }

        $mimeType = finfo_file($fileInfo, $fileName);
        finfo_close($fileInfo);

        return MimeType::tryFrom($mimeType) ?? false;
    }

    public static function bytesToHumanString(int $bytes, int $decimals = 2): string
    {
        $factor = intval(floor((strlen(strval($bytes)) - 1) / 3));
        if (!isset(self::SIZE_UNITS[$factor])) {
            return strval($bytes);
        }
        if ($factor === 0) {
            $decimals = 0;
        }

        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . self::SIZE_UNITS[$factor];
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
