<?php

declare(strict_types = 1);

use Dotenv\Dotenv;

use TryAgainLater\MediaConvertAppApi\FileController;

header("Access-Control-Allow-Origin: *");

define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('PROJECT_ROOT_PATH', dirname(__DIR__, levels: 2) . DIRECTORY_SEPARATOR);

if (!defined('STDOUT')) {
    define('STDOUT', fopen('php://stdout', 'wb'));
}

require_once ROOT_PATH . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$dotenv = Dotenv::createImmutable(PROJECT_ROOT_PATH);
$dotenv->safeLoad();

if (strtoupper($_SERVER['REQUEST_METHOD']) === 'POST' && $_SERVER['REQUEST_URI'] === '/upload') {
    $fileController = new FileController;
    $fileController->create();
}
