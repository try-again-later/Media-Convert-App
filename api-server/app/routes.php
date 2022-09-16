<?php

declare(strict_types=1);

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use Psr\Http\Message\{ResponseInterface as Response, ServerRequestInterface as Request};

use TryAgainLater\MediaConvertAppApi\Actions\Auth\{AuthAction, AuthCheckACtion};
use TryAgainLater\MediaConvertAppApi\Actions\Video\{DeleteVideoAction, ListVideosAction, VideoUploadAction};
use TryAgainLater\MediaConvertAppApi\Application\Settings;
use TryAgainLater\MediaConvertAppApi\Middleware\{
    AuthGuardMiddleware,
    FileUploadOptions,
    WithFileUploadsMiddleware,
};
use TryAgainLater\MediaConvertAppApi\Util\MimeType;

return function (App $app) {
    /** @var Settings */
    $settings = $app->getContainer()->get(Settings::class);

    // CORS Pre-Flight OPTIONS Request Handler
    $app
        ->options('/{routes:.*}', function (Request $request, Response $response) {
            return $response;
        });

    $app
        ->group('/videos', function (RouteCollectorProxy $group) use ($settings) {
            $group
                ->get('', ListVideosAction::class);

            $group
                ->post('/create', VideoUploadAction::class)
                ->add(new WithFileUploadsMiddleware(
                    new FileUploadOptions(
                        name: 'video',
                        uploadDirectory: $settings->get('uploadDirectory'),
                        maxSize: $settings->get('videoMaxSize'),
                        allowedMimeTypes: [
                            MimeType::VIDEO_WEBM,
                            MimeType::VIDEO_MP4,
                        ],
                    ),
                ));

            $group
                ->delete('/{key}', DeleteVideoAction::class);
        })
        ->add(AuthGuardMiddleware::class);

    $app
        ->map(['GET', 'POST'], '/auth-check', AuthCheckAction::class)
        ->add(AuthGuardMiddleware::class);

    $app
        ->post('/auth', AuthAction::class);
};
