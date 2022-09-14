<?php

declare(strict_types=1);

namespace TryAgainLater\MediaConvertAppApi\Domain\User;

interface UserRepository
{
    public function generateNewToken(): string;

    public function findUserWithToken(string $token): ?User;

    public function save(User $user): void;
}
