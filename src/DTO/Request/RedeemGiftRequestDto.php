<?php

declare(strict_types=1);

namespace App\DTO\Request;

use InvalidArgumentException;

final class RedeemGiftRequestDto
{
    public function __construct(
        private readonly int $memberId,
        private readonly int $giftId,
    ) {}

    public function getMemberId(): int
    {
        return $this->memberId;
    }

    public function getGiftId(): int
    {
        return $this->giftId;
    }

    public static function fromArray(array $payload): self
    {
        $memberId = $payload['member_id'] ?? null;
        $giftId = $payload['gift_id'] ?? null;

        if (!is_int($memberId) && !(is_string($memberId) && ctype_digit($memberId))) {
            throw new InvalidArgumentException('member_id must be a positive integer.');
        }

        if (!is_int($giftId) && !(is_string($giftId) && ctype_digit($giftId))) {
            throw new InvalidArgumentException('gift_id must be a positive integer.');
        }

        $memberId = (int) $memberId;
        $giftId = (int) $giftId;

        if ($memberId <= 0) {
            throw new InvalidArgumentException('member_id must be greater than 0.');
        }

        if ($giftId <= 0) {
            throw new InvalidArgumentException('gift_id must be greater than 0.');
        }

        return new self(
            memberId: $memberId,
            giftId: $giftId,
        );
    }
}
