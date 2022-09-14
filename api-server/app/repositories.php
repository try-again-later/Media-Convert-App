<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use function DI\autowire;

use TryAgainLater\MediaConvertAppApi\Domain\User\{UserRepository, VideoRepository};
use TryAgainLater\MediaConvertAppApi\Persistence\{MongoUserRepository, MongoVideoRepository};

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        UserRepository::class => autowire(MongoUserRepository::class),
        VideoRepository::class => autowire(MongoVideoRepository::class),
    ]);
};
