<?php

declare(strict_types = 1);

use Aws\S3\Exception\S3Exception;
use Dotenv\Dotenv;
use Aws\S3\S3Client;
use MongoDB\Client as MongoClient;
use MongoDB\Exception\Exception as MongoException;

use TryAgainLater\MediaConvertAppApi\FileController;
use TryAgainLater\MediaConvertAppApi\Response;

header("Access-Control-Allow-Origin: *");

define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('PROJECT_ROOT_PATH', dirname(__DIR__, levels: 2) . DIRECTORY_SEPARATOR);

require_once ROOT_PATH . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$dotenv = Dotenv::createImmutable(PROJECT_ROOT_PATH);
$dotenv->safeLoad();
$dotenv->required([
    'MINIO_ENDPOINT',
    'MINIO_ROOT_USER',
    'MINIO_ROOT_PASSWORD',

    'MONGO_HOST',
    'MONGO_PORT',
    'MONGO_USER',
    'MONGO_PASSWORD',
]);

if (strtoupper($_SERVER['REQUEST_METHOD']) === 'POST' && $_SERVER['REQUEST_URI'] === '/upload') {
    try {
        $s3Client = new S3Client([
            'version' => 'latest',
            'region' => 'us-east-1',
            'endpoint' => $_ENV['MINIO_ENDPOINT'],
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => $_ENV['MINIO_ROOT_USER'],
                'secret' => $_ENV['MINIO_ROOT_PASSWORD'],
            ],
        ]);

        $mongoUri = sprintf(
            'mongodb://%s:%s@%s:%s',
            $_ENV['MONGO_USER'],
            $_ENV['MONGO_PASSWORD'],
            $_ENV['MONGO_HOST'],
            $_ENV['MONGO_PORT'],
        );
        $mongoClient = new MongoClient($mongoUri);

        $fileController = new FileController(
            s3Client: $s3Client,
            mongoClient: $mongoClient,
        );
        echo $fileController->create();
    } catch (S3Exception | MongoException) {
        echo Response::json(Response::HTTP_INTERNAL_SERVER_ERROR);
    } catch (Throwable) {
        // do nothing...
    }
}
