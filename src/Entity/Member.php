<?php

declare(strict_types=1);

namespace App\Entity;

final class Member
{
    public function __construct(
        private readonly int $id,
        private readonly string $fullName,
        private readonly string $email,
        private readonly string $createdAt,
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }
}
