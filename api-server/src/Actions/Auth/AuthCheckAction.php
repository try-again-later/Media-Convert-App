<?php

declare(strict_types=1);

namespace TryAgainLater\MediaConvertAppApi\Actions\Auth;

use Psr\Http\Message\ResponseInterface as Response;

use TryAgainLater\MediaConvertAppApi\Actions\Action;

class AuthCheckACtion extends Action
{
    /** @inheritdoc */
    protected function action(): Response
    {
        return $this->respondWithData();
    }
}
