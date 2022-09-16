<?php

declare(strict_types=1);

namespace TryAgainLater\MediaConvertAppApi\Middleware;

use Psr\Http\Message\{ServerRequestInterface as Request, ResponseInterface as Response};
use Psr\Http\Server\{MiddlewareInterface as Middleware, RequestHandlerInterface as RequestHandler};

use TryAgainLater\MediaConvertAppApi\Domain\User\UserRepository;

class AuthMiddleware implements Middleware
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    /** @inheritdoc */
    public function process(Request $request, RequestHandler $handler): Response
    {
        /** @var ?string */
        $token = null;

        $queryParams = $request->getQueryParams();
        if (isset($queryParams['token'])) {
            $token = $queryParams['token'];
        }

        $parsedBody = $request->getParsedBody();
        if (isset($parsedBody['token'])) {
            $token = $parsedBody['token'];
        }

        $authUser = null;
        if ($token !== null) {
            $authUser = $this->userRepository->findUserWithToken($token);
        }

        $request = $request
            ->withAttribute('auth.check', $authUser !== null)
            ->withAttribute('auth.user', $authUser);

        return $handler->handle($request);
    }
}
