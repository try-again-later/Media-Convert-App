<?php

declare(strict_types=1);

namespace TryAgainLater\MediaConvertAppApi\Actions\Video;

use Psr\Container\ContainerInterface;

use TryAgainLater\MediaConvertAppApi\Domain\Video\VideoRepository;
use TryAgainLater\MediaConvertAppApi\Actions\Action;
use TryAgainLater\MediaConvertAppApi\Util\S3BucketAdapter;

abstract class VideoAction extends Action
{
    protected VideoRepository $videoRepository;
    protected S3BucketAdapter $videosS3Bucket;
    protected ContainerInterface $container;

    public function __construct(
        VideoRepository $videoRepository,
        ContainerInterface $container,
    ) {
        parent::__construct();

        $this->videoRepository = $videoRepository;
        $this->container = $container;
        $this->videosS3Bucket = $this->container->get('videosBucket');
    }
}
