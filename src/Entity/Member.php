<?php

declare(strict_types=1);

namespace App\Entity;

final class Member
{
    public function __construct(
        public readonly int $id,
        public readonly string $fullName,
        public readonly string $email,
        public readonly string $createdAt,
    ) {}
}
