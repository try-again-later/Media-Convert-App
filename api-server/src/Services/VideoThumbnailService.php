<?php

declare(strict_types=1);

namespace TryAgainLater\MediaConvertAppApi\Services;

use Psr\Container\ContainerInterface;

use TryAgainLater\MediaConvertAppApi\Application\Settings;
use TryAgainLater\MediaConvertAppApi\Util\S3BucketAdapter;

class VideoThumbnailService
{
    private S3BucketAdapter $videosThumbnailsBucket;
    private Settings $settings;

    public function __construct(ContainerInterface $container, Settings $settings)
    {
        $this->videosThumbnailsBucket = $container->get('videosThumbnailsBucket');
        $this->settings = $settings;
    }

    /**
     * @return string the public URL of the thumbnail.
     */
    public function createThumbnail(
        string $videoPath,
    ): ?string
    {
        $thumbnailsSettings = $this->settings->get('thumbnails');
        $videosSettings = $this->settings->get('videos');

        $videoPathInfo = pathinfo($videoPath);
        $thumbnailPath =
            $thumbnailsSettings['outputDirectory'] .
            $videoPathInfo['filename'] . '-thumbnail' .
            '.png';

        $thumbnailCreationResultCode = 1;
        exec(
            command: implode(' ', [
                'ffmpeg',
                '-y',
                '-i', "'$videoPath'",
                '-vf', sprintf(
                    'scale=%d:%d:force_original_aspect_ratio=decrease',
                    $thumbnailsSettings['width'],
                    $thumbnailsSettings['height'],
                ),
                '-ss', '00:00:01.000',
                '-vframes', 1,
                "'$thumbnailPath'",
            ]),
            result_code: $thumbnailCreationResultCode,
        );
        if ($thumbnailCreationResultCode !== 0 || !file_exists($thumbnailPath)) {
            return null;
        }

        [$key, $url] = $this->videosThumbnailsBucket->uploadFile(
            filePath: $thumbnailPath,
            expires: $videosSettings['expirationTime'],
        );

        return $url;
    }
}
