<?php

declare(strict_types=1);

namespace App\DTO\Request;

use InvalidArgumentException;

final class GetMemberPointHistoryRequestDto
{
    public function __construct(
        private readonly int $memberId,
    ) {}

    public function getMemberId(): int
    {
        return $this->memberId;
    }

    public static function fromArray(array $payload): self
    {
        $memberId = $payload['member_id'] ?? null;

        if (!is_int($memberId) && !(is_string($memberId) && ctype_digit($memberId))) {
            throw new InvalidArgumentException('member_id must be a positive integer.');
        }

        $memberId = (int) $memberId;

        if ($memberId <= 0) {
            throw new InvalidArgumentException('member_id must be greater than 0.');
        }

        return new self(
            memberId: $memberId,
        );
    }
}
