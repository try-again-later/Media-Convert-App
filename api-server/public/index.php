<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Aws\S3\S3Client;
use MongoDB\Client as MongoClient;

use TryAgainLater\MediaConvertAppApi\Controllers\FileController;
use TryAgainLater\MediaConvertAppApi\Models\User;
use TryAgainLater\MediaConvertAppApi\Request;
use TryAgainLater\MediaConvertAppApi\Response;

header("Access-Control-Allow-Origin: *");

define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('PROJECT_ROOT_PATH', dirname(__DIR__, levels: 2) . DIRECTORY_SEPARATOR);

require_once ROOT_PATH . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

function getS3Client(): S3Client
{
    return new S3Client([
        'version' => 'latest',
        'region' => 'us-east-1',
        'endpoint' => $_ENV['MINIO_ENDPOINT'],
        'use_path_style_endpoint' => true,
        'credentials' => [
            'key' => $_ENV['MINIO_ROOT_USER'],
            'secret' => $_ENV['MINIO_ROOT_PASSWORD'],
        ],
    ]);
}

function getMongoClient(): MongoClient
{
    $mongoUri = sprintf(
        'mongodb://%s:%s@%s:%s',
        $_ENV['MONGO_USER'],
        $_ENV['MONGO_PASSWORD'],
        $_ENV['MONGO_HOST'],
        $_ENV['MONGO_PORT'],
    );
    return new MongoClient($mongoUri);
}

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

try {
    if (
        Request::post() && Request::uriMatches('/upload') ||
        Request::get() && Request::uriMatches('/videos')
    ) {
        $mongoClient = getMongoClient();
        $user = User::auth($mongoClient);
        if ($user === false) {
            echo Response::text(Response::HTTP_UNAUTHORIZED);
            die();
        }

        $fileController = new FileController(
            s3Client: getS3Client(),
            mongoClient: $mongoClient,
            user: $user,
        );

        if (Request::uriMatches('/upload')) {
            echo $fileController->create();
        }
        if (Request::uriMatches('/videos')) {
            echo $fileController->index();
        }
    } else if (Request::post() && Request::uriMatches('/auth')) {
        $user = User::createNew(getMongoClient());
        if ($user === false) {
            echo Response::json(Response::HTTP_INTERNAL_SERVER_ERROR);
        } else {
            echo Response::json(Response::HTTP_OK, ['token' => $user->token()]);
        }
    }
} catch (Throwable $error) {
    echo Response::json(Response::HTTP_INTERNAL_SERVER_ERROR);
}
