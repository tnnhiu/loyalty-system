<?php

declare(strict_types=1);

namespace App\Entity;

final class Redemption
{
    public function __construct(
        public readonly int $id,
        public readonly int $memberId,
        public readonly int $giftId,
        public readonly int $pointsUsed,
        public readonly string $status,
        public readonly string $createdAt,
    ) {}
}
