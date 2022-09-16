<?php

declare(strict_types=1);

namespace TryAgainLater\MediaConvertAppApi\Util;

use Aws\S3\S3Client;
use Ramsey\Uuid\Uuid;

class S3BucketAdapter
{
    public function __construct(
        private S3Client $s3Client,
        private string $bucketName,
    ) {
    }

    public function truncate(): void
    {
        $objects = $this->s3Client->getIterator('ListObjects', [
            'Bucket' => $this->bucketName,
        ]);

        foreach ($objects as $object) {
            $this->s3Client->deleteObject([
                'Bucket' => $this->bucketName,
                'Key' => $object['Key'],
            ]);
        }
    }

    public function delete(): void
    {
        $this->s3Client->deleteBucket([
            'Bucket' => $this->bucketName,
        ]);
    }

    public function create(): void
    {
        $this->s3Client->createBucket([
            'Bucket' => $this->bucketName,
        ]);
    }

    public function exists(): bool
    {
        return $this->s3Client->doesBucketExistV2($this->bucketName, accept403: true);
    }

    public function migrate(bool $fresh = false): void
    {
        if ($fresh) {
            if ($this->exists()) {
                $this->truncate();
                $this->delete();
            }
            $this->create();
        } else if (!$this->exists()) {
            $this->create();
        }
    }

    public function getUniqueFileName(string $extension): string
    {
        while (true) {
            $uuid = Uuid::uuid4();
            $newFileName = $uuid . $extension;

            if (!$this->s3Client->doesObjectExist($this->bucketName, $newFileName)) {
                break;
            }
        }

        return $newFileName;
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function uploadFile(
        string $filePath,
        string $expires,
    ): array {
        $key = $this->getUniqueFileName(pathinfo($filePath, PATHINFO_EXTENSION));

        $this->s3Client->putObject([
            'Bucket' => $this->bucketName,
            'Key' => $key,
            'SourceFile' => $filePath,
        ]);

        $getObjectCommand = $this->s3Client->getCommand('GetObject', [
            'Bucket' => $this->bucketName,
            'Key' => $key,
        ]);
        $presignedRequest = $this->s3Client->createPresignedRequest(
            command: $getObjectCommand,
            expires: $expires,
        );

        return [$key, (string) $presignedRequest->getUri()];
    }
}
