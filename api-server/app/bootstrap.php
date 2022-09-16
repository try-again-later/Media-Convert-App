<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Dotenv\Dotenv;

define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('PROJECT_ROOT_PATH', dirname(__DIR__, levels: 2) . DIRECTORY_SEPARATOR);
require_once ROOT_PATH . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

date_default_timezone_set('UTC');

$dotenv = Dotenv::createImmutable(PROJECT_ROOT_PATH);
$dotenv->safeLoad();

try {
    $dotenv->required([
        'MINIO_ENDPOINT',
        'MINIO_ROOT_USER',
        'MINIO_ROOT_PASSWORD',

        'MONGO_HOST',
        'MONGO_PORT',
        'MONGO_USER',
        'MONGO_PASSWORD',
    ]);
    $dotenv->ifPresent('MONGO_PORT')->isInteger();
} catch (Throwable) {
    http_response_code(500);
    echo 'Internal server error';
    die();
}

$containerBuilder = new ContainerBuilder();

$settings = require ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'settings.php';
$settings($containerBuilder);

$dependencies = require ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'dependencies.php';
$dependencies($containerBuilder);

$repositories = require ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'repositories.php';
$repositories($containerBuilder);

$container = $containerBuilder->build();
