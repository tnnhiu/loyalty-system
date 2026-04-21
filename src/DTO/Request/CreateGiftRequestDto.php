<?php

declare(strict_types=1);

namespace App\DTO\Request;

use InvalidArgumentException;

final class CreateGiftRequestDto
{
    public function __construct(
        private readonly string $giftName,
        private readonly int $pointCost,
        private readonly int $stock,
        private readonly string $status,
    ) {}

    public function getGiftName(): string
    {
        return $this->giftName;
    }

    public function getPointCost(): int
    {
        return $this->pointCost;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public static function fromArray(array $payload): self
    {
        $giftName = $payload['gift_name'] ?? null;
        $pointCost = $payload['point_cost'] ?? null;
        $stock = $payload['stock'] ?? 0;
        $status = $payload['status'] ?? 'INACTIVE';

        if (!is_string($giftName) || trim($giftName) === '') {
            throw new InvalidArgumentException('gift_name is required.');
        }

        if (!is_int($pointCost) && !(is_string($pointCost) && ctype_digit($pointCost))) {
            throw new InvalidArgumentException('point_cost must be an integer.');
        }

        if (!is_int($stock) && !(is_string($stock) && ctype_digit($stock))) {
            throw new InvalidArgumentException('stock must be an integer.');
        }

        $pointCost = (int) $pointCost;
        $stock = (int) $stock;

        if ($pointCost <= 0) {
            throw new InvalidArgumentException('point_cost must be greater than 0.');
        }

        if ($stock < 0) {
            throw new InvalidArgumentException('stock must be greater than or equal to 0.');
        }

        if (!is_string($status)) {
            throw new InvalidArgumentException('status must be a string.');
        }

        $status = strtoupper(trim($status));
        if (!in_array($status, ['ACTIVE', 'INACTIVE'], true)) {
            throw new InvalidArgumentException('status must be ACTIVE or INACTIVE.');
        }

        return new self(
            giftName: trim($giftName),
            pointCost: $pointCost,
            stock: $stock,
            status: $status,
        );
    }
}
