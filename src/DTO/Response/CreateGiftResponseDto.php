<?php

declare(strict_types=1);

namespace App\DTO\Response;

final class CreateGiftResponseDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $giftName,
        public readonly int $pointCost,
        public readonly int $stock,
        public readonly string $status,
    ) {}

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
