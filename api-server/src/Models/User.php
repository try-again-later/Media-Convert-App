<?php

declare(strict_types = 1);

namespace TryAgainLater\MediaConvertAppApi\Models;

use DateTimeImmutable;

use MongoDB\{Client as MongoClient, Collection as MongoCollection};
use Ramsey\Uuid\Uuid;

use TryAgainLater\MediaConvertAppApi\Controllers\FileController;

class User
{
    private static function getCollection(MongoClient $mongoClient): MongoCollection
    {
        return $mongoClient->app->users;
    }

    public static function auth(MongoClient $mongoClient): self | bool
    {
        if (!isset($_REQUEST['token'])) {
            return false;
        }
        return self::getFromToken($mongoClient, $_REQUEST['token']);

    }

    public static function getFromToken(MongoClient $mongoClient, string $token): self | bool
    {
        $users = self::getCollection($mongoClient);

        $userData = $users->findOne(['token' => $token]);
        if (is_null($userData)) {
            return false;
        }
        return new self(
            mongoClient: $mongoClient,
            token: $token,
            new: false,
            videos: [...$userData['videos']],
        );
    }

    public static function createNew(MongoClient $mongoClient): self | bool
    {
        $users = self::getCollection($mongoClient);

        while (true) {
            $token = Uuid::uuid4()->toString();
            if (is_null($users->findOne(['token' => $token]))) {
                break;
            }
        }

        $users->insertOne([
            'token' => $token,
            'videos' => [],
        ]);

        return new self(
            mongoClient: $mongoClient,
            token: $token,
            new: true,
        );
    }

    public function __construct(
        private MongoClient $mongoClient,
        private bool $new,
        private string $token,
        private array $videos = [],
    )
    {
    }

    public function addVideo(
        string $key,
        string $url,
        string $originalName,
        DateTimeImmutable $uploadedAt,
        DateTimeImmutable $expiresAt,
        ?string $thumbnailUrl = null,
    )
    {
        $users = self::getCollection($this->mongoClient);
        $videoData = [
            'key' => $key,
            'url' => $url,
            'original_name' => $originalName,
            'uploaded_at' => $uploadedAt->format(FileController::DATE_FORMAT),
            'expires_at' => $expiresAt->format(FileController::DATE_FORMAT),
            'thumbnail_url' => $thumbnailUrl,
        ];
        $users->updateOne(
            ['token' => $this->token()],
            ['$push' => ['videos' => $videoData]],
        );
    }

    public function token(): string
    {
        return $this->token;
    }

    public function videos()
    {
        return $this->videos;
    }
}
