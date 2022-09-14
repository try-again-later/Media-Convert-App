<?php

declare(strict_types=1);

namespace TryAgainLater\MediaConvertAppApi\Actions\Video;

use Psr\Http\Message\ResponseInterface as Response;

use TryAgainLater\MediaConvertAppApi\Actions\Action;

class ListVideosAction extends Action
{
    /**
     * @inheritdoc
     */
    protected function action(): Response
    {
        return $this->respondWithData([
            'videos' => [],
        ]);
    }
}
