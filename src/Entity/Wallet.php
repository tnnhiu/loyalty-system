<?php

declare(strict_types=1);

namespace App\Entity;

final class Wallet
{
    public function __construct(
        public readonly int $id,
        public readonly int $memberId,
        public readonly string $balance,
    ) {}
}
