<?php

declare(strict_types = 1);

namespace TryAgainLater\MediaConvertAppApi;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Ramsey\Uuid\Uuid;

class S3BucketAdapter
{
    public function __construct(
        private S3Client $s3Client,
        private string $bucket,
    )
    {
    }

    public function truncateBucket(): bool
    {
        try {
            $objects = $this->s3Client->getIterator('ListObjects', [
                'Bucket' => $this->bucket,
            ]);
            foreach ($objects as $object) {
                $this->s3Client->deleteObject([
                    'Bucket' => $this->bucket,
                    'Key' => $object['Key'],
                ]);
            }

            return true;
        } catch (S3Exception) {
            return false;
        }
    }

    public function getUniqueFileName(string $extension): string
    {
        while (true) {
            $uuid = Uuid::uuid4();
            $newFileName = $uuid . $extension;

            if (!$this->s3Client->doesObjectExist($this->bucket, $newFileName)) {
                break;
            }
        }

        return $newFileName;
    }

    public function uploadFile(
        string $key,
        string $filePath,
        string $expires,
    ): string | null
    {
        try {
            $this->s3Client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
                'SourceFile' => $filePath,
            ]);

            $getObjectCommand = $this->s3Client->getCommand('GetObject', [
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);
            $presignedRequest = $this->s3Client->createPresignedRequest(
                command: $getObjectCommand,
                expires: $expires,
            );
            return (string) $presignedRequest->getUri();
        } catch (S3Exception) {
            return null;
        }
    }
}
