<?php

declare(strict_types=1);

namespace TryAgainLater\MediaConvertAppApi\Domain\Video;

use DateTimeImmutable;
use JsonSerializable;
use ReturnTypeWillChange;

use TryAgainLater\MediaConvertAppApi\Domain\User\User;

class Video implements JsonSerializable
{
    public const DATE_FORMAT = 'D M d Y H:i:s O';

    public function __construct(
        private User $owner,
        private string $key,
        private DateTimeImmutable $expiresAt,
        private DateTimeImmutable $uploadedAt,
        private string $originalName,
        private ?string $url = null,
        private ?string $thumbnailUrl = null,
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getUploadedAt(): DateTimeImmutable
    {
        return $this->uploadedAt;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function getThumbnailUrl(): ?string
    {
        return $this->thumbnailUrl;
    }

    public function getOwner(): User
    {
        return $this->owner;
    }

    #[ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return [
            'key' => $this->getKey(),
            'expires_at' => $this->getExpiresAt()->format(self::DATE_FORMAT),
            'uploaded_at' => $this->getUploadedAt()->format(self::DATE_FORMAT),
            'original_name' => $this->getOriginalName(),
            'url' => $this->getUrl(),
            'thumbnail_url' => $this->getThumbnailUrl(),
        ];
    }
}
