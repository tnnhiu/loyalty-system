<?php

declare(strict_types=1);

namespace App\DTO\Response;

final class CreateGiftResponseDto
{
    public function __construct(
        private readonly int $id,
        private readonly string $giftName,
        private readonly int $pointCost,
        private readonly int $stock,
        private readonly string $status,
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

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

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'gift_name' => $this->giftName,
            'point_cost' => $this->pointCost,
            'stock' => $this->stock,
            'status' => $this->status,
        ];
    }
}
