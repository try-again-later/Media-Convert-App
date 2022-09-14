<?php

declare(strict_types=1);

namespace TryAgainLater\MediaConvertAppApi\Actions\Video;

use Psr\Http\Message\ResponseInterface as Response;

use TryAgainLater\MediaConvertAppApi\Actions\Action;
use TryAgainLater\MediaConvertAppApi\Domain\User\UserRepository;

class ListVideosAction extends Action
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        parent::__construct();
        $this->userRepository = $userRepository;
    }

    /** @inheritdoc */
    protected function action(): Response
    {
        return $this->respondWithData([
            'videos' => [],
        ]);
    }
}
