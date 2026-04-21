<?php

declare(strict_types=1);

namespace App\DTO\Response;

final class PointHistoryItemDto
{
    public function __construct(
        private readonly int $pointId,
        private readonly int $pointAmount,
        private readonly ?string $description,
        private readonly ?int $transactionId,
        private readonly ?string $transactionAmount,
        private readonly ?int $redemptionId,
        private readonly ?int $giftId,
        private readonly ?string $giftName,
        private readonly string $createdAt,
    ) {}

    public function getPointId(): int
    {
        return $this->pointId;
    }

    public function getPointAmount(): int
    {
        return $this->pointAmount;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getTransactionId(): ?int
    {
        return $this->transactionId;
    }

    public function getTransactionAmount(): ?string
    {
        return $this->transactionAmount;
    }

    public function getRedemptionId(): ?int
    {
        return $this->redemptionId;
    }

    public function getGiftId(): ?int
    {
        return $this->giftId;
    }

    public function getGiftName(): ?string
    {
        return $this->giftName;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            'point_id' => $this->pointId,
            'point_amount' => $this->pointAmount,
            'description' => $this->description,
            'transaction_id' => $this->transactionId,
            'transaction_amount' => $this->transactionAmount,
            'redemption_id' => $this->redemptionId,
            'gift_id' => $this->giftId,
            'gift_name' => $this->giftName,
            'created_at' => $this->createdAt,
        ];
    }
}
