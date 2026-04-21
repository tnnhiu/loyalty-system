<?php

declare(strict_types=1);

namespace App\DTO\Response;

final class CreateMemberResponseDto
{
    public function __construct(
        private readonly int $memberId,
        private readonly string $fullName,
        private readonly string $email,
        private readonly string $createdAt,
        private readonly int $walletId,
        private readonly string $walletBalance,
    ) {}

    public function getMemberId(): int
    {
        return $this->memberId;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getWalletId(): int
    {
        return $this->walletId;
    }

    public function getWalletBalance(): string
    {
        return $this->walletBalance;
    }

    public function toArray(): array
    {
        return [
            'member' => [
                'id' => $this->memberId,
                'full_name' => $this->fullName,
                'email' => $this->email,
                'created_at' => $this->createdAt,
            ],
            'wallet' => [
                'id' => $this->walletId,
                'balance' => $this->walletBalance,
            ],
        ];
    }
}
