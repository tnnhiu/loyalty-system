<?php

declare(strict_types=1);

namespace App\DTO\Response;

final class RedeemGiftResponseDto
{
    public function __construct(
        private readonly int $redemptionId,
        private readonly int $memberId,
        private readonly int $giftId,
        private readonly string $giftName,
        private readonly int $pointsUsed,
        private readonly string $walletBalance,
        private readonly string $status,
        private readonly string $createdAt,
    ) {}

    public function getRedemptionId(): int
    {
        return $this->redemptionId;
    }

    public function getMemberId(): int
    {
        return $this->memberId;
    }

    public function getGiftId(): int
    {
        return $this->giftId;
    }

    public function getGiftName(): string
    {
        return $this->giftName;
    }

    public function getPointsUsed(): int
    {
        return $this->pointsUsed;
    }

    public function getWalletBalance(): string
    {
        return $this->walletBalance;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

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
