<?php

declare(strict_types=1);

namespace App\Entity;

final class Point
{
    public function __construct(
        public readonly int $id,
        public readonly int $walletId,
        public readonly ?int $transactionId,
        public readonly ?int $redemptionId,
        public readonly int $pointAmount,
        public readonly ?string $description,
        public readonly string $createdAt,
    ) {}
}
