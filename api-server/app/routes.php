<?php

declare(strict_types=1);

use Slim\App;
use Psr\Http\Message\{ResponseInterface as Response, ServerRequestInterface as Request};

use TryAgainLater\MediaConvertAppApi\Actions\Auth\{AuthAction, AuthCheckACtion};
use TryAgainLater\MediaConvertAppApi\Actions\Video\ListVideosAction;
use TryAgainLater\MediaConvertAppApi\Middleware\AuthGuardMiddleware;

return function (App $app) {
    // CORS Pre-Flight OPTIONS Request Handler
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        return $response;
    });

    $app
        ->get('/videos', ListVideosAction::class)
        ->add(AuthGuardMiddleware::class);

    $app
        ->map(['GET', 'POST'], '/auth-check', AuthCheckAction::class)
        ->add(AuthGuardMiddleware::class);

    $app->post('/auth', AuthAction::class);
};
