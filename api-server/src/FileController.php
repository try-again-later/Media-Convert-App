<?php

declare(strict_types = 1);

namespace TryAgainLater\MediaConvertAppApi;

use ZMQContext;
use ZMQ;

class FileController
{
    // 20 Mb
    private const MAX_SIZE = 20 * 1024 * 1024;

    private const ALLOWED_FILES = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
    ];

    private static function getMimeType(string $fileName): string | false
    {
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($fileInfo === false) {
            return false;
        }

        $mimeType = finfo_file($fileInfo, $fileName);
        finfo_close($fileInfo);

        return $mimeType;
    }

    public function create()
    {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            return;
        }

        $tempName = $_FILES['file']['tmp_name'];

        if (filesize($tempName) > self::MAX_SIZE) {
            http_response_code(400);
            return;
        }

        $mimeType = self::getMimeType($tempName);
        if (!in_array($mimeType, array_keys(self::ALLOWED_FILES))) {
            http_response_code(400);
            return;
        }

        $newFileName =
            pathinfo($_FILES['file']['name'], PATHINFO_FILENAME) .
            '.' .
            self::ALLOWED_FILES[$mimeType];

        $uploadPath =
            dirname(__DIR__) .
            DIRECTORY_SEPARATOR .
            'uploads' .
            DIRECTORY_SEPARATOR .
            $newFileName;

        $moveSuccess = move_uploaded_file($tempName, $uploadPath);
        if (!$moveSuccess) {
            http_response_code(400);
            return;
        }

        $context = new ZMQContext();
        $socket = $context->getSocket(ZMQ::SOCKET_PUSH, 'my pusher');
        $socket->connect('tcp://localhost:5555');
        $socket->send($newFileName);

        http_response_code(200);
    }
}
