<?php

declare(strict_types=1);

use Slim\App;
use Slim\Middleware\MethodOverrideMiddleware;

use TryAgainLater\MediaConvertAppApi\Middleware\AuthMiddleware;

return function (App $app) {
    $app->add(MethodOverrideMiddleware::class);
    $app->add(AuthMiddleware::class);
};
