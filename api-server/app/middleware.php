<?php

declare(strict_types=1);

use Slim\App;
use TryAgainLater\MediaConvertAppApi\Middleware\AuthMiddleware;

return function (App $app) {
    $app->add(AuthMiddleware::class);
};
