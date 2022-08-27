<?php

declare(strict_types = 1);

namespace TryAgainLater\MediaConvertAppApi;

use ZMQContext;
use ZMQ;

use Aws\S3\S3Client;
use MongoDB\Client as MongoClient;

class FileController
{
    public const MAX_SIZE = 100 * 1024 * 1024; // 100 Mb
    public const ALLOWED_FILES = ['video/webm', 'video/mp4'];

    public const UPLOADED_VIDEOS_BUCKET = 'uploaded-videos';
    public const VIDEO_EXPIRATION_TIME = '+24 hours';

    public function __construct(
        private S3Client $s3Client,
        private MongoClient $mongoClient,
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

        $uploadedVideos = new S3BucketAdapter($this->s3Client, self::UPLOADED_VIDEOS_BUCKET);

        $newFileName = $uploadedVideos->getUniqueFileName(
            extension: FileUtils::getExtensionFromMimeType($mimeType)
        );

        $url = $uploadedVideos->uploadFile(
            key: $newFileName,
            filePath: $tempName,
            expires: self::VIDEO_EXPIRATION_TIME,
        );
        if ($url === false) {
            return Response::json(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $context = new ZMQContext();
        $socket = $context->getSocket(ZMQ::SOCKET_PUSH, 'my pusher');
        $socket->connect('tcp://localhost:5555');
        $socket->send($newFileName);

        return Response::json(
            Response::HTTP_OK,
            [
                'message' => "File '$newFileName' successfully uploaded!",
                'url' => $url,
            ],
        );
    }
}
