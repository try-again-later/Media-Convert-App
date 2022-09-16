<?php

declare(strict_types=1);

namespace TryAgainLater\MediaConvertAppApi\Actions\Auth;

use Psr\Http\Message\ResponseInterface as Response;

use TryAgainLater\MediaConvertAppApi\Actions\Action;
use TryAgainLater\MediaConvertAppApi\Domain\User\User;
use TryAgainLater\MediaConvertAppApi\Domain\User\UserRepository;

class AuthAction extends Action
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
        $token = $this->userRepository->generateNewToken();
        $user = new User($token);
        $this->userRepository->save($user);

        return $this->respondWithData(['token' => $token]);
    }
}
