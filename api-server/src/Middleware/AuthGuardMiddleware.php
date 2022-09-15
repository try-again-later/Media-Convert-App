<?php

declare(strict_types=1);

namespace TryAgainLater\MediaConvertAppApi\Middleware;

use Psr\Http\Message\{ServerRequestInterface as Request, ResponseInterface as Response};
use Psr\Http\Server\{MiddlewareInterface as Middleware, RequestHandlerInterface as RequestHandler};
use Slim\Exception\HttpUnauthorizedException;

class AuthGuardMiddleware implements Middleware
{
    /** @inheritdoc */
    public function process(Request $request, RequestHandler $handler): Response
    {
        if ($request->getAttribute('auth.check', false)) {
            return $handler->handle($request);
        }

        throw new HttpUnauthorizedException($request);
    }
}
