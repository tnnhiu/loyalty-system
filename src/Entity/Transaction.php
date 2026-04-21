<?php

declare(strict_types=1);

namespace App\Entity;

final class Transaction
{
    public function __construct(
        private readonly int $id,
        private readonly int $memberId,
        private readonly string $amount,
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

    public function getAmount(): string
    {
        return $this->amount;
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
