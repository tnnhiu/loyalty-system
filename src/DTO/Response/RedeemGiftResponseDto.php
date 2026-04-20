<?php

declare(strict_types=1);

namespace App\DTO\Response;

final class RedeemGiftResponseDto
{
    public function __construct(
        public readonly int $redemptionId,
        public readonly int $memberId,
        public readonly int $giftId,
        public readonly string $giftName,
        public readonly int $pointsUsed,
        public readonly string $walletBalance,
        public readonly string $status,
        public readonly string $createdAt,
    ) {}

    public function toArray(): array
    {
        return [
            'redemption_id' => $this->redemptionId,
            'member_id' => $this->memberId,
            'gift_id' => $this->giftId,
            'gift_name' => $this->giftName,
            'points_used' => $this->pointsUsed,
            'wallet_balance' => $this->walletBalance,
            'status' => $this->status,
            'created_at' => $this->createdAt,
        ];
    }
}
