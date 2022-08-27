<?php

declare(strict_types = 1);

namespace TryAgainLater\MediaConvertAppApi\Controllers;

use TryAgainLater\MediaConvertAppApi\{Response, FileUtils, S3BucketAdapter};

use ZMQContext;
use ZMQ;

use Aws\S3\S3Client;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use MongoDB\Client as MongoClient;

use TryAgainLater\MediaConvertAppApi\Models\User;

class FileController
{
    public const MAX_SIZE = 100 * 1024 * 1024; // 100 Mb
    public const ALLOWED_MIME_TYPES = ['video/webm', 'video/mp4'];

    public const UPLOADED_VIDEOS_BUCKET = 'uploaded-videos';
    public const UPLAODED_VIDEOS_THUMBNAILS_BUCKET = 'uploaded-videos-thumbnails';
    public const VIDEO_EXPIRATION_TIME = '+24 hours';
    public const DATE_FORMAT = 'D M d Y H:i:s O';

    public function __construct(
        private string $temporaryFilesFolder,
        private S3Client $s3Client,
        private MongoClient $mongoClient,
        private User $user,
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
        $originalName = pathinfo($_FILES['file']['name'], PATHINFO_BASENAME);

        if (filesize($tempName) > self::MAX_SIZE) {
            $maxHumanSize = FileUtils::bytesToHumanString(self::MAX_SIZE);
            return Response::json(
                Response::HTTP_BAD_REQUEST,
                ['error' => "Max file upload size is $maxHumanSize."]
            );
        }

        $mimeType = FileUtils::getMimeType($tempName);
        if (!in_array(strtolower($mimeType), self::ALLOWED_MIME_TYPES, strict: true)) {
            return Response::json(
                Response::HTTP_BAD_REQUEST,
                ['error' => "Invalid file format. File format '$mimeType' is not allowed."]
            );
        }

        $uploadedVideos = new S3BucketAdapter($this->s3Client, self::UPLOADED_VIDEOS_BUCKET);

        $newFileName = $uploadedVideos->getUniqueFileName(
            extension: FileUtils::getExtensionFromMimeType($mimeType)
        );

        $uploadedAt = CarbonImmutable::now();
        $expiresAt = new CarbonImmutable(strtotime(self::VIDEO_EXPIRATION_TIME));

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

        $thumbnailUrl = null;
        $thumbnailPath = $this->temporaryFilesFolder . 'thumbnail.png';
        if (file_exists($thumbnailPath)) {
            unlink($thumbnailPath);
        }
        $thumbnailResult = null;
        exec(
            command: implode(' ', [
                'ffmpeg',
                '-y',
                '-i', "'$tempName'",
                '-vf', 'scale=320:320:force_original_aspect_ratio=decrease',
                '-ss', '00:00:01.000',
                '-vframes', '1',
                "'$thumbnailPath'",
            ]),
            result_code: $thumbnailResult,
        );
        if ($thumbnailResult !== 0) {
            return Response::json(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        if (file_exists($thumbnailPath)) {
            $thumbnails = new S3BucketAdapter(
                $this->s3Client,
                self::UPLAODED_VIDEOS_THUMBNAILS_BUCKET,
            );

            $thumbnailUrl = $thumbnails->uploadFile(
                key: $newFileName,
                filePath: $thumbnailPath,
                expires: self::VIDEO_EXPIRATION_TIME,
            );
        }

        $this->user->addVideo(
            key: $newFileName,
            url: $url,
            originalName: $originalName,
            uploadedAt: $uploadedAt,
            expiresAt: $expiresAt,
            thumbnailUrl: $thumbnailUrl,
        );

        return Response::json(
            Response::HTTP_OK,
            [
                'key' => $newFileName,
                'url' => $url,
                'expires_at' => $expiresAt->format(self::DATE_FORMAT),
                'original_name' => $originalName,
                'thumbnail_url' => $thumbnailUrl,
            ],
        );
    }

    public function index()
    {
        $aliveVideos = [];
        foreach ($this->user->videos() as $videoData) {
            if (Carbon::now()->gte($videoData['expires_at'])) {
                continue;
            }
            $aliveVideos[] = [
                'key' => $videoData['key'],
                'url' => $videoData['url'],
                'expires_at' => $videoData['expires_at'],
                'original_name' => $videoData['original_name'],
                'thumbnail_url' => $videoData['thumbnail_url'] ?? null,
            ];
        }

        return Response::json(
            Response::HTTP_OK,
            ['videos' => $aliveVideos],
        );
    }
}
