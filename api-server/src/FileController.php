<?php

declare(strict_types = 1);

namespace TryAgainLater\MediaConvertAppApi;

use ZMQContext;
use ZMQ;

use Ramsey\Uuid\Uuid;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

class FileController
{
    public const MAX_SIZE = 100 * 1024 * 1024; // 100 Mb
    public const ALLOWED_FILES = ['video/webm', 'video/mp4'];

    public const UPLOADED_VIDEOS_BUCKET = 'uploaded-videos';

    public function __construct(
        private S3Client $s3Client,
    )
    {
    }

    public function create(): string
    {
        if (
            !isset($_FILES['file']) ||
            !is_string($_FILES['file']['name']) ||
            $_FILES['file']['error'] !== UPLOAD_ERR_OK
        ) {
            return Response::json(
                Response::HTTP_BAD_REQUEST,
                ['error' => 'You can only upload exactly one file at a time.']
            );
        }

        $tempName = $_FILES['file']['tmp_name'];

        if (filesize($tempName) > self::MAX_SIZE) {
            $maxHumanSize = FileUtils::bytesToHumanString(self::MAX_SIZE);
            return Response::json(
                Response::HTTP_BAD_REQUEST,
                ['error' => "Max file upload size is $maxHumanSize."]
            );
        }

        $mimeType = FileUtils::getMimeType($tempName);
        if (!in_array(strtolower($mimeType), self::ALLOWED_FILES, strict: true)) {
            return Response::json(
                Response::HTTP_BAD_REQUEST,
                ['error' => "Invalid file format. File format '$mimeType' is not allowed."]
            );
        }

        $newFileName = null;
        while (true) {
            $uuid = Uuid::uuid4();
            $extension = FileUtils::getExtensionFromMimeType($mimeType);
            $newFileName = $uuid . $extension;

            if (!$this->s3Client->doesObjectExist(self::UPLOADED_VIDEOS_BUCKET, $newFileName)) {
                break;
            }
        }
        if ($newFileName == null) {
            return Response::json(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            $this->s3Client->putObject([
                'Bucket' => self::UPLOADED_VIDEOS_BUCKET,
                'Key' => $newFileName,
                'SourceFile' => $tempName,
            ]);
        } catch (S3Exception) {
            return Response::json(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $context = new ZMQContext();
        $socket = $context->getSocket(ZMQ::SOCKET_PUSH, 'my pusher');
        $socket->connect('tcp://localhost:5555');
        $socket->send($newFileName);

        return Response::json(
            Response::HTTP_OK,
            ['message' => "File '$newFileName' successfully uploaded!"],
        );
    }
}
