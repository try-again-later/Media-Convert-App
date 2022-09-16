<?php

declare(strict_types=1);

namespace TryAgainLater\MediaConvertAppApi\Actions\Video;

use Psr\Http\Message\{ResponseInterface as Response};

use TryAgainLater\MediaConvertAppApi\Domain\User\User;

class DeleteVideoAction extends VideoAction
{
    protected function action(): Response
    {
        $videoKey = $this->args['key'];

        /** @var User */
        $owner = $this->request->getAttribute('auth.user');

        $this->videoRepository->deleteVideo($owner, $videoKey);
        $this->videosS3Bucket->deleteFile($videoKey);

        return $this->respondWithData();
    }
}
