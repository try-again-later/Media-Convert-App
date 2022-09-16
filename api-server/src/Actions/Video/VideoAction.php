<?php

declare(strict_types=1);

namespace TryAgainLater\MediaConvertAppApi\Actions\Video;

use TryAgainLater\MediaConvertAppApi\Domain\Video\VideoRepository;
use TryAgainLater\MediaConvertAppApi\Actions\Action;

abstract class VideoAction extends Action
{
    protected VideoRepository $videoRepository;

    public function __construct(VideoRepository $videoRepository)
    {
        parent::__construct();
        $this->videoRepository = $videoRepository;
    }
}
