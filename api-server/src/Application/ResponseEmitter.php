<?php

declare(strict_types=1);

namespace TryAgainLater\MediaConvertAppApi\Application;

use Slim\ResponseEmitter as SlimResponseEmitter;
use Psr\Http\Message\ResponseInterface as Response;

class ResponseEmitter extends SlimResponseEmitter
{
    /**
     * @inheritdoc
     */
    public function emit(Response $response): void
    {
        $response = $response
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader(
                'Access-Control-Allow-Headers',
                'X-Requested-With, Content-Type, Accept, Origin, Authorization',
            )
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');

        if (ob_get_contents()) {
            ob_clean();
        }

        parent::emit($response);
    }
}
