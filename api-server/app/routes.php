<?php

declare(strict_types=1);

use Slim\App;
use Psr\Http\Message\{ResponseInterface as Response, ServerRequestInterface as Request};
use TryAgainLater\MediaConvertAppApi\Actions\Video\ListVideosAction;

return function (App $app) {
    // CORS Pre-Flight OPTIONS Request Handler
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        return $response;
    });

    $app->get('/videos', ListVideosAction::class);
};
