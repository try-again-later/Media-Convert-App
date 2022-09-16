<?php

declare(strict_types=1);

namespace TryAgainLater\MediaConvertAppApi\Actions\Video;

use Carbon\CarbonImmutable;
use Psr\Http\Message\{ResponseInterface as Response};

use TryAgainLater\MediaConvertAppApi\Domain\User\User;
use TryAgainLater\MediaConvertAppApi\Domain\Video\{Video};
use TryAgainLater\MediaConvertAppApi\Util\S3BucketAdapter;

class VideoUploadAction extends VideoAction
{
    public const UPLOADED_VIDEO_EXPIRATION_TIME = '+24 hours';

    /** @inheritdoc */
    protected function action(): Response
    {
        $uploadedVideo = $this->request->getAttribute('files.video');

        /** @var S3BucketAdapter */
        $uploadedVideosS3Bucket = $this->container->get('videosBucket');

        [$key, $url] = $uploadedVideosS3Bucket->uploadFile(
            filePath: $uploadedVideo['path'],
            expires: self::UPLOADED_VIDEO_EXPIRATION_TIME,
        );
        $uploadedAt = CarbonImmutable::now();
        $expiresAt = new CarbonImmutable(strtotime(self::UPLOADED_VIDEO_EXPIRATION_TIME));

        /** @var User */
        $owner = $this->request->getAttribute('auth.user');
        $video = new Video(
            owner: $owner,
            key: $key,
            expiresAt: $expiresAt,
            uploadedAt: $uploadedAt,
            originalName: $uploadedVideo['originalName'],
            url: $url,
        );
        $this->videoRepository->pushNewVideo($video);

        return $this->respondWithData([
            'video' => $video->jsonSerialize(),
        ]);
    }
}
