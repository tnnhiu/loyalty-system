<?php

declare(strict_types=1);

namespace App\DTO\Response;

final class PointHistoryItemDto
{
    public function __construct(
        public readonly int $pointId,
        public readonly int $pointAmount,
        public readonly ?string $description,
        public readonly ?int $transactionId,
        public readonly ?string $transactionAmount,
        public readonly ?int $redemptionId,
        public readonly ?int $giftId,
        public readonly ?string $giftName,
        public readonly string $createdAt,
    ) {}

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
