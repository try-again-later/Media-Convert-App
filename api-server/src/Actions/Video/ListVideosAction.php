<?php

declare(strict_types=1);

namespace TryAgainLater\MediaConvertAppApi\Actions\Video;

use Carbon\Carbon;
use Psr\Http\Message\ResponseInterface as Response;

use TryAgainLater\MediaConvertAppApi\Domain\User\User;

class ListVideosAction extends VideoAction
{
    /** @inheritdoc */
    protected function action(): Response
    {
        /** @var User */
        $owner = $this->request->getAttribute('auth.user');

        $videos = $this->videoRepository->findUserVideos($owner);
        $aliveVideos = [];

        foreach ($videos as $video) {
            if (Carbon::now()->gte($video->getExpiresAt())) {
                continue;
            }

            $aliveVideos[] = $video->jsonSerialize();
        }

        return $this->respondWithData([
            'videos' => $aliveVideos,
        ]);
    }
}
