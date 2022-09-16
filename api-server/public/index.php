<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Slim\Factory\{AppFactory, ServerRequestCreatorFactory};
use Dotenv\Dotenv;
use TryAgainLater\MediaConvertAppApi\Application\{HttpErrorHandler, ResponseEmitter, Settings};

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

AppFactory::setContainer($container);
$app = AppFactory::create();

$middleware = require ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'middleware.php';
$middleware($app);

$routes = require ROOT_PATH . 'app' . DIRECTORY_SEPARATOR . 'routes.php';
$routes($app);

/** @var Settings */
$settings = $container->get(Settings::class);

$callableResolver = $app->getCallableResolver();
$responseFactory = $app->getResponseFactory();
$errorHandler = new HttpErrorHandler($callableResolver, $responseFactory);
$errorMiddleware = $app->addErrorMiddleware(
    displayErrorDetails: $settings->get('displayErrorDetails', default: false),
    logErrors: $settings->get('logErrors', default: false),
    logErrorDetails: $settings->get('logErrorDetails', default: false),
);
$errorMiddleware->setDefaultErrorHandler($errorHandler);

$serverRequestCreator = ServerRequestCreatorFactory::create();
$request = $serverRequestCreator->createServerRequestFromGlobals();

$response = $app->handle($request);
$responseEmitter = new ResponseEmitter();
$responseEmitter->emit($response);
