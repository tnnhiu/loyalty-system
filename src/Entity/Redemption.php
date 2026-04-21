<?php

declare(strict_types=1);

namespace App\Entity;

final class Redemption
{
    public function __construct(
        private readonly int $id,
        private readonly int $memberId,
        private readonly int $giftId,
        private readonly int $pointsUsed,
        private readonly string $status,
        private readonly string $createdAt,
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getMemberId(): int
    {
        return $this->memberId;
    }

    public function getGiftId(): int
    {
        return $this->giftId;
    }

    public function getPointsUsed(): int
    {
        return $this->pointsUsed;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }
}
