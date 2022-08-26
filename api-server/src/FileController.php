<?php

declare(strict_types = 1);

namespace TryAgainLater\MediaConvertAppApi;

use ZMQContext;
use ZMQ;

use Ramsey\Uuid\Uuid;

class FileController
{
    public const MAX_SIZE = 100 * 1024 * 1024; // 100 Mb
    public const ALLOWED_FILES = ['video/webm', 'video/mp4'];

    private string $filesUploadDirectory;

    public function __construct(?string $filesUploadDirectory = null)
    {
        if (isset($filesUploadDirectory)) {
            $this->filesUploadDirectory = $filesUploadDirectory;
        } else {
            $this->filesUploadDirectory =
                dirname(__DIR__) .
                DIRECTORY_SEPARATOR .
                'uploads' .
                DIRECTORY_SEPARATOR;
        }
    }

    public function create()
    {
        if (
            !isset($_FILES['file']) ||
            !is_string($_FILES['file']['name']) ||
            $_FILES['file']['error'] !== UPLOAD_ERR_OK
        ) {
            Response::json(
                Response::HTTP_BAD_REQUEST,
                ['error' => 'You can only upload exactly one file at a time.']
            );
            die();
        }

        $tempName = $_FILES['file']['tmp_name'];

        if (filesize($tempName) > self::MAX_SIZE) {
            $maxHumanSize = FileUtils::bytesToHumanString(self::MAX_SIZE);
            Response::json(
                Response::HTTP_BAD_REQUEST,
                ['error' => "Max file upload size is $maxHumanSize."]
            );
            die();
        }

        $mimeType = FileUtils::getMimeType($tempName);
        if (!in_array(strtolower($mimeType), self::ALLOWED_FILES, strict: true)) {
            Response::json(
                Response::HTTP_BAD_REQUEST,
                ['error' => "Invalid file format. File format '$mimeType' is not allowed."]
            );
            die();
        }

        $extension = FileUtils::getExtensionFromMimeType($mimeType) ?: '';

        $uploadPath = '';
        while (true) {
            $uuid = Uuid::uuid4();
            $uploadPath = $this->filesUploadDirectory . $uuid->toString() . $extension;

            if (!file_exists($uploadPath)) {
                break;
            }
        }

        $newFileName = pathinfo($uploadPath, PATHINFO_BASENAME);

        $moveSuccess = move_uploaded_file($tempName, $uploadPath);
        if (!$moveSuccess) {
            Response::json(
                Response::HTTP_INTERNAL_SERVER_ERROR,
                ['error' => 'Internal server error.'],
            );
            die();
        }

        $context = new ZMQContext();
        $socket = $context->getSocket(ZMQ::SOCKET_PUSH, 'my pusher');
        $socket->connect('tcp://localhost:5555');
        $socket->send($newFileName);

        Response::json(
            Response::HTTP_OK,
            ['message' => "File '$newFileName' successfully uploaded!"],
        );
        die();
    }
}
