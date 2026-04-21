<?php

declare(strict_types=1);

namespace App\DTO\Response;

final class GetMemberPointHistoryResponseDto
{
    /** @param PointHistoryItemDto[] $items */
    public function __construct(
        private readonly int $memberId,
        private readonly int $walletId,
        private readonly string $walletBalance,
        private readonly int $totalItems,
        private readonly array $items,
    ) {}

    public function getMemberId(): int
    {
        return $this->memberId;
    }

    public function getWalletId(): int
    {
        return $this->walletId;
    }

    public function getWalletBalance(): string
    {
        return $this->walletBalance;
    }

    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    /** @return PointHistoryItemDto[] */
    public function getItems(): array
    {
        return $this->items;
    }

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
