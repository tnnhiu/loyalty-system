<?php

declare(strict_types=1);

namespace App\Entity;

final class Point
{
    public function __construct(
        private readonly int $id,
        private readonly int $walletId,
        private readonly ?int $transactionId,
        private readonly ?int $redemptionId,
        private readonly int $pointAmount,
        private readonly ?string $description,
        private readonly string $createdAt,
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getWalletId(): int
    {
        return $this->walletId;
    }

    public function getTransactionId(): ?int
    {
        return $this->transactionId;
    }

    public function getRedemptionId(): ?int
    {
        return $this->redemptionId;
    }

    public function getPointAmount(): int
    {
        return $this->pointAmount;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }
}
