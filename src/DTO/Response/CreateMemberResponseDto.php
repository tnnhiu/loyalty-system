<?php

declare(strict_types=1);

namespace App\DTO\Response;

final class CreateMemberResponseDto
{
    public function __construct(
        public readonly int $memberId,
        public readonly string $fullName,
        public readonly string $email,
        public readonly string $createdAt,
        public readonly int $walletId,
        public readonly string $walletBalance,
    ) {}

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
