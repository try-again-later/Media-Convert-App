<?php

declare(strict_types=1);

namespace TryAgainLater\MediaConvertAppApi\Actions\Video;

use Carbon\CarbonImmutable;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\{ResponseInterface as Response};

use TryAgainLater\MediaConvertAppApi\Application\Settings;
use TryAgainLater\MediaConvertAppApi\Domain\User\User;
use TryAgainLater\MediaConvertAppApi\Domain\Video\{Video, VideoRepository};
use TryAgainLater\MediaConvertAppApi\Services\VideoThumbnailService;
use TryAgainLater\MediaConvertAppApi\Util\S3BucketAdapter;

class VideoUploadAction extends VideoAction
{
    private VideoThumbnailService $thumbnailService;
    private Settings $settings;

    public function __construct(
        VideoRepository $videoRepository,
        ContainerInterface $container,
        VideoThumbnailService $thumbnailService,
        Settings $settings,
    ) {
        parent::__construct(videoRepository: $videoRepository, container: $container);
        $this->thumbnailService = $thumbnailService;
        $this->settings = $settings;
    }

    /** @inheritdoc */
    protected function action(): Response
    {
        $uploadedVideo = $this->request->getAttribute('files.video');

        /** @var S3BucketAdapter */
        $uploadedVideosS3Bucket = $this->container->get('videosBucket');

        $videoSettings = $this->settings->get('videos');

        [$key, $url] = $uploadedVideosS3Bucket->uploadFile(
            filePath: $uploadedVideo['path'],
            expires: $videoSettings['expirationTime'],
        );
        $uploadedAt = CarbonImmutable::now();
        $expiresAt = new CarbonImmutable(strtotime($videoSettings['expirationTime']));

        $thumbnailUrl = $this->thumbnailService->createThumbnail($uploadedVideo['path']);

        /** @var User */
        $owner = $this->request->getAttribute('auth.user');
        $video = new Video(
            owner: $owner,
            key: $key,
            expiresAt: $expiresAt,
            uploadedAt: $uploadedAt,
            originalName: $uploadedVideo['originalName'],
            url: $url,
            thumbnailUrl: $thumbnailUrl,
        );
        $this->videoRepository->pushNewVideo($video);

        return $this->respondWithData([
            'video' => $video->jsonSerialize(),
        ]);
    }
}
