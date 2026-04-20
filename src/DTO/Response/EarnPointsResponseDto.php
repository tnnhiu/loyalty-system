<?php

declare(strict_types=1);

namespace App\DTO\Response;

final class EarnPointsResponseDto
{
    public function __construct(
        public readonly int $transactionId,
        public readonly int $memberId,
        public readonly string $amount,
        public readonly int $earnedPoints,
        public readonly string $walletBalance,
        public readonly string $status,
        public readonly string $createdAt,
    ) {}

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
