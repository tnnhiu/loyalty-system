<?php

declare(strict_types=1);

namespace App\Entity;

final class Transaction
{
    public function __construct(
        public readonly int $id,
        public readonly int $memberId,
        public readonly string $amount,
        public readonly string $status,
        public readonly string $createdAt,
    ) {}
}
