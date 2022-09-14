<?php

declare(strict_types=1);

namespace TryAgainLater\MediaConvertAppApi\Domain\User;

use MongoDB\BSON\ObjectId;

use TryAgainLater\MediaConvertAppApi\Domain\Video\Video;

class User
{
    /**
     * @param Video[] $videos
     */
    public function __construct(
        private string $token,
        private array $videos = [],
        private ?ObjectId $id = null,
    ) {
    }

    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return Video[]
     */
    public function getVideos(): array
    {
        return $this->videos;
    }

    public function getId(): ?ObjectId
    {
        return $this->id;
    }

    public function setId(?ObjectId $id): void
    {
        $this->id = $id;
    }

    /**
     * @param Video[] $videos
     */
    public function setVideos(array $videos): void
    {
        $this->videos = $videos;
    }
}
