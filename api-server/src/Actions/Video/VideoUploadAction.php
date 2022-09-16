<?php

declare(strict_types=1);

namespace TryAgainLater\MediaConvertAppApi\Actions\Video;

use RuntimeException;

use Carbon\CarbonImmutable;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\{ResponseInterface as Response, UploadedFileInterface};
use Slim\Exception\HttpBadRequestException;

use TryAgainLater\MediaConvertAppApi\Application\Settings;
use TryAgainLater\MediaConvertAppApi\Domain\User\User;
use TryAgainLater\MediaConvertAppApi\Domain\Video\{Video, VideoRepository};
use TryAgainLater\MediaConvertAppApi\Util\{FileUtils, S3BucketAdapter};

class VideoUploadAction extends VideoAction
{
    public const MAX_SIZE = 100 * 1024 * 1024; // 100Mb
    public const ALLOWED_MIME_TYPES = [
        'video/webm',
        'video/mp4',
    ];
    public const UPLOADED_VIDEO_EXPIRATION_TIME = '+24 hours';

    private Settings $settings;
    private ContainerInterface $container;

    public function __construct(
        VideoRepository $videoRepository,
        Settings $settings,
        ContainerInterface $container,
    ) {
        parent::__construct($videoRepository);
        $this->settings = $settings;
        $this->container = $container;
    }

    /** @inheritdoc */
    protected function action(): Response
    {
        /** @var UploadedFileInterface[] */
        $uploadedFiles = $this->request->getUploadedFiles();

        if (count($uploadedFiles) !== 1 || !isset($uploadedFiles['video'])) {
            throw new HttpBadRequestException(
                $this->request,
                'You can only upload exactly one file at a time.',
            );
        }

        $uploadedFile = $uploadedFiles['video'];

        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            throw new HttpBadRequestException(
                $this->request,
                'Failed to upload the file.',
            );
        }

        $uploadedFileName = FileUtils::moveUploadedFile(
            directory: $this->settings->get('uploadDirectory'),
            uploadedFile: $uploadedFile,
        );
        $uploadedFilePath = $this->settings->get('uploadDirectory') . $uploadedFileName;

        if (filesize($uploadedFilePath) > self::MAX_SIZE) {
            $maxSizeForHumans = FileUtils::bytesToHumanString(self::MAX_SIZE);
            throw new HttpBadRequestException(
                $this->request,
                "Max file upload size is {$maxSizeForHumans}.",
            );
        }

        $uploadedFileMimeType = FileUtils::getMimeType($uploadedFilePath);
        if (!in_array(strtolower($uploadedFileMimeType), self::ALLOWED_MIME_TYPES, strict: true)) {
            throw new HttpBadRequestException(
                $this->request,
                "Invalid file type. Mime type '{$uploadedFileMimeType}' is not allowed.",
            );
        }

        /** @var S3BucketAdapter */
        $uploadedVideosS3Bucket = $this->container->get('videosBucket');
        $key = $uploadedVideosS3Bucket->getUniqueFileName(
            extension: FileUtils::getExtensionFromMimeType($uploadedFileMimeType),
        );
        $url = $uploadedVideosS3Bucket->uploadFile(
            key: $key,
            filePath: $uploadedFilePath,
            expires: self::UPLOADED_VIDEO_EXPIRATION_TIME,
        );
        if ($url === false) {
            throw new RuntimeException('Failed to upload the file to S3 storage.');
        }
        $uploadedAt = CarbonImmutable::now();
        $expiresAt = new CarbonImmutable(strtotime(self::UPLOADED_VIDEO_EXPIRATION_TIME));

        /** @var User */
        $owner = $this->request->getAttribute('auth.user');
        $video = new Video(
            owner: $owner,
            key: $key,
            expiresAt: $expiresAt,
            uploadedAt: $uploadedAt,
            originalName: $uploadedFile->getClientFilename(),
            url: $url,
        );
        $this->videoRepository->pushNewVideo($video);

        return $this->respondWithData([
            'video' => $video->jsonSerialize(),
        ]);
    }
}
