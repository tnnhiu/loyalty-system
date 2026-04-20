<?php

declare(strict_types=1);

namespace App\Entity;

final class Gift
{
    public function __construct(
        public readonly int $id,
        public readonly string $giftName,
        public readonly int $pointCost,
        public readonly int $stock,
        public readonly string $status,
    ) {}
}
