<?php

declare(strict_types=1);

namespace App\Entity;

final class Wallet
{
    public function __construct(
        private readonly int $id,
        private readonly int $memberId,
        private readonly string $balance,
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getMemberId(): int
    {
        return $this->memberId;
    }

    public function getBalance(): string
    {
        return $this->balance;
    }
}
