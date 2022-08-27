<?php

declare(strict_types = 1);

use Aws\S3\S3Client;
use Dotenv\Dotenv;

use TryAgainLater\MediaConvertAppApi\Controllers\FileController;
use TryAgainLater\MediaConvertAppApi\S3BucketAdapter;

define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('PROJECT_ROOT_PATH', dirname(__DIR__, levels: 2) . DIRECTORY_SEPARATOR);

require_once ROOT_PATH . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$fresh = false;
if (isset($argv[1]) && $argv[1] === 'fresh') {
    $fresh = true;
}

$dotenv = Dotenv::createImmutable(PROJECT_ROOT_PATH);
$dotenv->safeLoad();
$dotenv->required([
    'MINIO_ENDPOINT',
    'MINIO_ROOT_USER',
    'MINIO_ROOT_PASSWORD',
]);

$s3Client = new S3Client([
    'version' => 'latest',
    'region' => 'us-east-1',
    'use_path_style_endpoint' => true,
    'endpoint' => $_ENV['MINIO_ENDPOINT'],
    'credentials' => [
        'key' => $_ENV['MINIO_ROOT_USER'],
        'secret' => $_ENV['MINIO_ROOT_PASSWORD'],
    ],
]);

$migrateBucket = function (string $bucketName) use ($s3Client, $fresh)
{
    $bucket = new S3BucketAdapter($s3Client, $bucketName);

    if ($fresh && $s3Client->doesBucketExist($bucketName)) {
        $bucket->truncateBucket();

        $s3Client->deleteBucket([
            'Bucket' => $bucketName,
        ]);
    }

    $s3Client->createBucket([
        'Bucket' => $bucketName,
    ]);
};

$migrateBucket(FileController::UPLOADED_VIDEOS_BUCKET);
$migrateBucket(FileController::UPLAODED_VIDEOS_THUMBNAILS_BUCKET);
