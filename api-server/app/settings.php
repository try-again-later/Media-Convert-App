<?php

declare(strict_types=1);

use DI\ContainerBuilder;

use TryAgainLater\MediaConvertAppApi\Application\Settings;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        Settings::class => fn () => new Settings([
            'displayErrorDetails' => true,
            'logErrors' => true,
            'logErrorDetails' => true,
            'uploadDirectory' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR,
            'videoMaxSize' => 100 * 1024 * 1024,
            'minio' => [
                'endpoint' => $_ENV['MINIO_ENDPOINT'],
                'key' => $_ENV['MINIO_ROOT_USER'],
                'secret' => $_ENV['MINIO_ROOT_PASSWORD'],
            ],
            'mongo' => [
                'host' => $_ENV['MONGO_HOST'],
                'port' => intval($_ENV['MONGO_PORT']),
                'user' => $_ENV['MONGO_USER'],
                'password' => $_ENV['MONGO_PASSWORD'],
            ],
        ]),
    ]);
};
