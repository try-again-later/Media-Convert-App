<?php

declare(strict_types=1);

use Slim\Factory\{AppFactory, ServerRequestCreatorFactory};
use TryAgainLater\MediaConvertAppApi\Application\{HttpErrorHandler, ResponseEmitter, Settings};

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'bootstrap.php';

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
