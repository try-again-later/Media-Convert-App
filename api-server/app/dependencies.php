<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Aws\S3\{S3Client, S3ClientInterface};
use Psr\Container\ContainerInterface;
use MongoDB\Client as MongoClient;

use TryAgainLater\MediaConvertAppApi\Application\Settings;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        S3ClientInterface::class => function (ContainerInterface $container) {
            /** @var Settings */
            $settings = $container->get(Settings::class);
            $minioSettings = $settings->get('minio');

            return new S3Client([
                'version' => 'latest',
                'region' => 'us-east-1',
                'endpoint' => $minioSettings['endpoint'],
                'use_path_style_endpoint' => true,
                'credentials' => [
                    'key' => $minioSettings['key'],
                    'secret' => $minioSettings['secret'],
                ],
            ]);
        },

        MongoClient::class => function (ContainerInterface $container) {
            /** @var Settings */
            $settings = $container->get(Settings::class);
            $mongoSettings = $settings->get('mongo');

            $mongoUri = sprintf(
                'mongodb://%s:%s@%s:%s',
                $mongoSettings['user'],
                $mongoSettings['password'],
                $mongoSettings['host'],
                $mongoSettings['port'],
            );

            return new MongoClient($mongoUri);
        },
    ]);
};
