<?php

declare(strict_types=1);

namespace TryAgainLater\MediaConvertAppApi\Persistence;

use MongoDB\{Client as MongoClient, Collection as MongoCollection};
use Ramsey\Uuid\Uuid;
use TryAgainLater\MediaConvertAppApi\Domain\User\{User, UserRepository};

class MongoUserRepository implements UserRepository
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
    public function generateNewToken(): string
    {
        $token = Uuid::uuid4()->toString();
        return $token;
    }

    /** @inheritdoc */
    public function findUserWithToken(string $token): ?User
    {
        $users = $this->getUsersCollection();

        $userData = $users->findOne(['token' => $token]);
        if ($userData === null) {
            return null;
        }

        return new User(
            token: $token,
            id: $userData['_id'],
        );
    }

    /** @inheritdoc */
    public function save(User $user): void
    {
        $users = $this->getUsersCollection();

        if ($user->getId() === null) {
            $insertResult = $users->insertOne([
                'token' => $user->getToken(),
                'videos' => $user->getVideos(),
            ]);

            if ($insertResult->getInsertedCount() === 0) {
                throw new PersistenceException;
            }

            $user->setId($insertResult->getInsertedId());
        } else {
            $videosData = [];
            foreach ($user->getVideos() as $video) {
                $videosData[] = [];
            }

            $users->findOneAndUpdate(
                ['_id' => $user->getId()],
                [
                    '$set' => [
                        'token' => $user->getToken(),
                        'videos' => $videosData,
                    ],
                ],
            );
        }
    }

    /** @inheritdoc */
    public function dropAll(): void
    {
        $this->mongoClient->app->dropCollection('users');
    }
}
