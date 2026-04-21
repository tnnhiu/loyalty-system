<?php

declare(strict_types=1);

namespace App\DTO\Response;

final class EarnPointsResponseDto
{
    public function __construct(
        private readonly int $transactionId,
        private readonly int $memberId,
        private readonly string $amount,
        private readonly int $earnedPoints,
        private readonly string $walletBalance,
        private readonly string $status,
        private readonly string $createdAt,
    ) {}

    public function getTransactionId(): int
    {
        return $this->transactionId;
    }

    public function getMemberId(): int
    {
        return $this->memberId;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getEarnedPoints(): int
    {
        return $this->earnedPoints;
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
            'transaction_id' => $this->transactionId,
            'member_id' => $this->memberId,
            'amount' => $this->amount,
            'earned_points' => $this->earnedPoints,
            'wallet_balance' => $this->walletBalance,
            'status' => $this->status,
            'created_at' => $this->createdAt,
        ];
    }
}
