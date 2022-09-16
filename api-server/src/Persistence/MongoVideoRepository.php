<?php

declare(strict_types=1);

namespace TryAgainLater\MediaConvertAppApi\Persistence;

use Carbon\CarbonImmutable;
use MongoDB\{Client as MongoClient, Collection as MongoCollection};

use TryAgainLater\MediaConvertAppApi\Domain\DomainRecordNotFoundException;
use TryAgainLater\MediaConvertAppApi\Domain\User\User;
use TryAgainLater\MediaConvertAppApi\Domain\Video\{Video, VideoRepository};

class MongoVideoRepository implements VideoRepository
{
    public function __construct(
        private MongoClient $mongoClient,
    ) {
    }

    private function getUsersCollection(): MongoCollection
    {
        return $this->mongoClient->app->users;
    }

    /** @inheritdoc */
    public function findUserVideos(User $owner): array
    {
        $users = $this->getUsersCollection();

        $userData = $users->findOne(['token' => $owner->getToken()]);

        if ($userData === null) {
            throw new DomainRecordNotFoundException;
        }

        $videosData = $userData['videos'] ?? [];
        $videos = array_map(
            fn ($videoData) => new Video(
                owner: $owner,
                key: $videoData['key'],
                expiresAt: new CarbonImmutable($videoData['expires_at']),
                uploadedAt: new CarbonImmutable($videoData['uploaded_at']),
                originalName: $videoData['original_name'] ?? null,
                url: $videoData['url'] ?? null,
                thumbnailUrl: $videoData['thumbnail_url'] ?? null,
            ),
            [...$videosData],
        );

        return $videos;
    }

    /** @inheritdoc */
    public function pushNewVideo(Video $video): void
    {
        $users = $this->getUsersCollection();

        $videoData = $video->jsonSerialize();

        $pushResult = $users->updateOne(
            ['token' => $video->getOwner()->getToken()],
            ['$push' => ['videos' => $videoData]],
        );

        if ($pushResult->getMatchedCount() !== 1) {
            throw new DomainRecordNotFoundException;
        }

        if ($pushResult->getModifiedCount() !== 1 || !$pushResult->isAcknowledged()) {
            throw new PersistenceException;
        }
    }

    /** @inheritdoc */
    public function updateVideo(Video $video): void
    {
        $users = $this->getUsersCollection();

        $videoData = $video->jsonSerialize();

        $updateResult = $users->updateOne(
            [
                'token' => $video->getOwner()->getToken(),
                'videos.key' => $video->getKey(),
            ],
            [
                '$set' => ['videos.$' => $videoData],
            ],
        );

        if ($updateResult->getMatchedCount() !== 1) {
            throw new DomainRecordNotFoundException;
        }

        if ($updateResult->getModifiedCount() !== 1 || !$updateResult->isAcknowledged()) {
            throw new PersistenceException;
        }
    }

    /** @inheritdoc */
    public function deleteVideo(User $owner, string $key): void
    {
        $users = $this->getUsersCollection();

        $pullResult = $users->updateOne(
            [
                'token' => $owner->getToken(),
            ],
            [
                '$pull' => [
                    'videos' => ['key' => $key],
                ],
            ],
        );

        if ($pullResult->getMatchedCount() !== 1 || $pullResult->getModifiedCount() !== 1) {
            throw new DomainRecordNotFoundException;
        }

        if (!$pullResult->isAcknowledged()) {
            throw new PersistenceException;
        }
    }
}
