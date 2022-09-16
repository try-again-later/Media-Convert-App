<?php

declare(strict_types=1);

namespace TryAgainLater\MediaConvertAppApi\Domain\Video;

use TryAgainLater\MediaConvertAppApi\Domain\User\User;
use TryAgainLater\MediaConvertAppApi\Domain\Video\Video;

interface VideoRepository
{
    /** @return Video[] */
    public function findUserVideos(User $owner): array;

    public function pushNewVideo(Video $video): void;

    public function updateVideo(Video $video): void;

    public function deleteVideo(User $owner, string $key): void;
}
