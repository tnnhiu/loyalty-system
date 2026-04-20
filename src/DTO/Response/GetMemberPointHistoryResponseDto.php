<?php

declare(strict_types=1);

namespace App\DTO\Response;

final class GetMemberPointHistoryResponseDto
{
    /** @param PointHistoryItemDto[] $items */
    public function __construct(
        public readonly int $memberId,
        public readonly int $walletId,
        public readonly string $walletBalance,
        public readonly int $totalItems,
        public readonly array $items,
    ) {}

    public function toArray(): array
    {
        return [
            'member_id' => $this->memberId,
            'wallet_id' => $this->walletId,
            'wallet_balance' => $this->walletBalance,
            'total_items' => $this->totalItems,
            'items' => array_map(
                static fn(PointHistoryItemDto $item): array => $item->toArray(),
                $this->items
            ),
        ];
    }
}
